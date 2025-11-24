<?php
declare(strict_types=1);

/**
 * Script d'analyse SOLID avec inspecta-ai (version PHP)
 *
 * Usage CLI :
 *   php .github/scripts/check-solid.php [BASE_REF] [HEAD_REF]
 *
 * Exemple :
 *   php .github/scripts/check-solid.php HEAD^ HEAD
 */

function println(string $message = ''): void
{
    echo $message . PHP_EOL;
}

/**
 * Exécute une commande shell et retourne la sortie (stdout).
 * Si $allowFailure = true, ne lance pas d'exception en cas de code != 0.
 */
function runCommand(string $cmd, bool $allowFailure = false): string
{
    $output = [];
    $code   = 0;
    exec($cmd . ' 2>&1', $output, $code);

    if ($code !== 0 && !$allowFailure) {
        throw new RuntimeException("Commande échouée ($code): {$cmd}\n" . implode("\n", $output));
    }

    return implode("\n", $output);
}

/**
 * Appelle inspecta-ai pour analyser un fichier et renvoie la sortie JSON.
 */
function callInspectaAi(string $filePath): string
{
    $cmd = sprintf(
        './vendor/bin/inspecta-ai analyze solid_violations %s 2>&1',
        escapeshellarg($filePath)
    );

    println("📋 Commande exécutée: {$cmd}");

    $output = [];
    $code   = 0;
    exec($cmd, $output, $code);

    if ($code !== 0) {
        throw new RuntimeException("Commande inspecta-ai échouée ($code): {$cmd}\n" . implode("\n", $output));
    }

    return implode("\n", $output);
}

/**
 * Parse le JSON retourné par inspecta-ai.
 * Extrait le JSON même s'il y a du texte avant ou après (logs, warnings, etc.).
 */
function parseInspectaAiJson(string $jsonOutput): ?array
{
    // Essai direct de parsing (cas le plus courant)
    $decoded = json_decode(trim($jsonOutput), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }
    
    // Si échec, on cherche le JSON dans la sortie (extraction)
    $firstBracePos = strpos($jsonOutput, '{');
    if ($firstBracePos === false) {
        return null;
    }
    
    // On cherche la dernière accolade fermante qui forme un JSON valide
    $substr = substr($jsonOutput, $firstBracePos);
    $length = strlen($substr);
    $best = null;
    $bestLength = 0;
    
    for ($i = 0; $i < $length; $i++) {
        if ($substr[$i] !== '}') {
            continue;
        }
        
        $candidate = substr($substr, 0, $i + 1);
        $decoded = json_decode($candidate, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // On garde le JSON le plus long (le plus complet)
            if (strlen($candidate) > $bestLength) {
                $best = $decoded;
                $bestLength = strlen($candidate);
            }
        }
    }
    
    return $best;
}


/**
 * Échappe un texte pour les "workflow commands" GitHub (annotations).
 * On doit encoder %, \r, \n.
 */
function escapeForGithubCommand(string $text): string
{
    return strtr($text, [
        '%'  => '%25',
        "\r" => '%0D',
        "\n" => '%0A',
    ]);
}

/**
 * Émet une annotation GitHub (warning/error) pour afficher un message sur une ligne de fichier.
 * On autorise des messages multi-lignes via l'encodage %0A.
 */
function emitAnnotation(string $severity, string $file, ?int $line, string $title, string $message): void
{
    $severity = strtolower($severity);
    $level    = $severity === 'major' ? 'error' : 'warning';

    $titleSafe   = escapeForGithubCommand($title);
    $messageSafe = escapeForGithubCommand($message);

    if ($line !== null && $line > 0) {
        printf(
            "::%s file=%s,line=%d,title=%s::%s\n",
            $level,
            $file,
            $line,
            $titleSafe,
            $messageSafe
        );
    } else {
        printf(
            "::%s file=%s,title=%s::%s\n",
            $level,
            $file,
            $titleSafe,
            $messageSafe
        );
    }
}

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

$baseRef = $argv[1] ?? 'HEAD^';
$headRef = $argv[2] ?? 'HEAD';

println("🔍 Analyse SOLID avec inspecta-ai");
println("📊 Comparaison: {$baseRef}..{$headRef}");
println();

println("Recherche des fichiers PHP modifiés...");
$diffOutput = runCommand(sprintf(
    'git diff --name-only %s %s',
    escapeshellarg($baseRef),
    escapeshellarg($headRef)
), true);

$allFiles = array_filter(
    array_map('trim', explode("\n", $diffOutput)),
    static fn(string $f): bool => $f !== ''
);

// On garde uniquement :
// - fichiers .php
// - dans src/ ou tests/
$files = array_filter(
    $allFiles,
    static fn(string $f): bool =>
        str_ends_with($f, '.php')
        && (str_starts_with($f, 'src/')
            || str_starts_with($f, 'tests/'))
);

if (empty($files)) {
    println("✅ Aucun fichier PHP modifié dans src/ ou tests/, analyse SOLID ignorée.");
    exit(0);
}

println("📝 Fichiers PHP modifiés (dans src/ ou tests/) détectés:");
foreach ($files as $f) {
    println("  - {$f}");
}
println();

$workspace  = getenv('GITHUB_WORKSPACE') ?: getcwd();
$reportDir  = $workspace . '/.github/solid-reports';
@mkdir($reportDir, 0777, true);
$reportFile = $reportDir . '/solid-report.md';

$report = "# 🔍 Rapport d'analyse SOLID\n\n";
$report .= "Analyse effectuée avec **inspecta-ai** sur les fichiers PHP modifiés (src/ et tests/).\n\n";

$failed = false;

foreach ($files as $file) {
    println(str_repeat('━', 78));
    println("📄 Analyse de: {$file}\n");

    if (!is_file($file)) {
        println("⚠️  Fichier supprimé, ignoré.\n");
        continue;
    }

    println("🤖 Interrogation de l'IA via inspecta-ai...");
    try {
        $jsonOutput = callInspectaAi($file);
        $json = parseInspectaAiJson($jsonOutput);
        
        if ($json === null) {
            println("⚠️  Impossible de parser le JSON retourné par inspecta-ai pour {$file}.");
            println("Erreur JSON: " . json_last_error_msg());
            println("Réponse brute complète (longueur: " . strlen($jsonOutput) . " caractères):");
            println($jsonOutput);
            println();
            continue;
        }
    } catch (RuntimeException $e) {
        println("❌ Erreur lors de l'appel à inspecta-ai pour {$file}:");
        println($e->getMessage());
        println();
        continue;
    }

    println("📊 Résultat de l'analyse (JSON) :");
    println(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    println();

    $solidOk  = (bool)($json['solid_ok'] ?? false);
    $score    = (int)($json['score'] ?? 0);
    $problems = is_array($json['problems'] ?? null) ? $json['problems'] : [];

    $problemsCount = count($problems);

    $majorProblems = array_values(array_filter(
        $problems,
        static fn(array $p): bool => ($p['severity'] ?? '') === 'major'
    ));

    // --- Ajout au rapport ---
    $report .= "\n## 📄 {$file}\n\n";
    if ($solidOk) {
        $report .= "✅ **Statut**: Conforme aux principes SOLID\n\n";
    } else {
        $report .= "❌ **Statut**: Violations SOLID détectées\n\n";
    }

    $report .= "**Score**: {$score}/100\n";
    $report .= "**Problèmes détectés**: {$problemsCount} (" . count($majorProblems) . " majeurs)\n\n";

    foreach ($problems as $p) {
        $principle = $p['principle'] ?? 'N/A';
        $severity  = $p['severity'] ?? 'unknown';
        $summary   = $p['summary'] ?? '';
        $suggest   = $p['suggestion'] ?? '';
        $line      = isset($p['line']) ? (int)$p['line'] : null;
        $steps     = $p['refactor_steps'] ?? null;

        if (!is_array($steps)) {
            $steps = [];
        }

        $report .= "### {$principle} - {$severity}\n\n";
        if ($line !== null && $line > 0) {
            $report .= "**Ligne**: {$line}\n\n";
        }
        $report .= "**Problème**: {$summary}\n\n";
        $report .= "**Suggestion**: {$suggest}\n\n";

        if (!empty($steps)) {
            $report .= "**Étapes de refactorisation proposées** :\n\n";
            foreach ($steps as $step) {
                $report .= "- " . $step . "\n";
            }
            $report .= "\n";
        }

        // --- Annotation GitHub multi-ligne pour ce problème ---
        $title = "SOLID {$principle} ({$severity})";

        $message = $summary;
        if ($suggest !== '') {
            $message .= "\nSuggestion : " . $suggest;
        }
        if (!empty($steps)) {
            $message .= "\nÉtapes de refactorisation :";
            foreach ($steps as $step) {
                $message .= "\n- " . $step;
            }
        }

        emitAnnotation($severity, $file, $line, $title, $message);
    }

    // --- Statut global CI ---
    if (!$solidOk && count($majorProblems) > 0) {
        println("❌ Violations SOLID majeures détectées dans {$file}");
        $failed = true;
    } elseif ($solidOk) {
        println("✅ Fichier conforme aux principes SOLID");
    } else {
        println("⚠️  Violations mineures détectées (ne bloque pas la CI)");
    }

    println();
}

// Écriture du rapport
file_put_contents($reportFile, $report);

println(str_repeat('━', 78));
println("\n📋 Résumé de l'analyse:");
println($report);
println();

// Chemin du rapport pour la CI
file_put_contents($reportDir . '/report-path.txt', $reportFile);

if ($failed) {
    println("❌ Au moins un fichier contient des violations SOLID majeures.");
    println("📄 Rapport complet disponible dans: {$reportFile}");
    exit(1);
}

println("✅ Analyse SOLID terminée : aucun problème majeur détecté.");
exit(0);
