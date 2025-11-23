<?php
declare(strict_types=1);

/**
 * Script d'analyse SOLID avec Ollama (version PHP)
 *
 * Usage CLI :
 *   php .github/scripts/check-solid.php [MODEL_NAME] [BASE_REF] [HEAD_REF]
 *
 * Exemple :
 *   php .github/scripts/check-solid.php llama3.2 HEAD^ HEAD
 */

function println(string $message = ''): void
{
    echo $message . PHP_EOL;
}

/**
 * Exécute une commande shell et retourne la sortie (stdout).
 * Lance une exception en cas d'erreur non 0 (sauf si $allowFailure = true).
 */
function runCommand(string $cmd, bool $allowFailure = false): string
{
    $output = [];
    $code = 0;
    exec($cmd . ' 2>&1', $output, $code);

    if ($code !== 0 && !$allowFailure) {
        throw new RuntimeException("Commande échouée ($code): $cmd\n" . implode("\n", $output));
    }

    return implode("\n", $output);
}

/**
 * Appelle Ollama avec un prompt donné et renvoie la sortie brute (stdout+stderr).
 */
function callOllama(string $model, string $prompt): string
{
    // On écrit le prompt dans un fichier temporaire
    $tmp = tempnam(sys_get_temp_dir(), 'ollama_prompt_');
    if ($tmp === false) {
        throw new RuntimeException('Impossible de créer un fichier temporaire pour le prompt');
    }

    file_put_contents($tmp, $prompt);

    // On fait exactement ce que tu faisais en bash :
    //   cat prompt | ollama run model 2>&1
    $cmd = sprintf(
        'cat %s | ollama run %s 2>&1',
        escapeshellarg($tmp),
        escapeshellarg($model)
    );

    $output = [];
    $code   = 0;
    exec($cmd, $output, $code);

    unlink($tmp);

    if ($code !== 0) {
        // On ne throw pas forcément, on laisse le JSON extractor décider
        // mais on logge un minimum
        return implode("\n", $output);
    }

    return implode("\n", $output);
}


/**
 * Supprime les séquences ANSI (couleurs, spinner, etc.).
 */
function stripAnsi(string $text): string
{
    return preg_replace('/\x1B\[[0-9;?]*[ -\/]*[@-~]/', '', $text) ?? $text;
}

/**
 * Essaye d'extraire un JSON valide de la sortie d'Ollama.
 * Stratégie : on cherche tous les blocs entre la première '{' et chaque '}', et
 * on prend le dernier qui passe json_decode.
 */
function extractJson(string $raw): ?array
{
    $clean = stripAnsi($raw);

    $firstBracePos = strpos($clean, '{');
    if ($firstBracePos === false) {
        return null;
    }

    $substr = substr($clean, $firstBracePos);
    $length = strlen($substr);

    $best = null;

    for ($i = 0; $i < $length; $i++) {
        if ($substr[$i] !== '}') {
            continue;
        }

        $candidate = substr($substr, 0, $i + 1);

        // Certains modèles renvoient \n littéraux : on les interprète
        $candidate = stripcslashes($candidate);

        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $best = $decoded;
        }
    }

    return $best;
}

/**
 * Construit le prompt SOLID pour un fichier donné.
 */
function buildPrompt(string $filePath, string $fileContent): string
{
    $basePrompt = <<<'PROMPT'
Tu es un expert PHP/Symfony et des principes SOLID.

Analyse le fichier suivant et détermine s'il respecte les principes SOLID, en particulier :
- SRP (Single Responsibility Principle) : une classe doit avoir une seule raison de changer
- OCP (Open/Closed Principle) : ouvert à l'extension, fermé à la modification
- LSP (Liskov Substitution Principle) : les objets dérivés doivent être substituables à leurs classes de base
- ISP (Interface Segregation Principle) : préférer plusieurs interfaces spécifiques à une interface générale
- DIP (Dependency Inversion Principle) : dépendre d'abstractions, pas de concrétions

IMPORTANT: Réponds UNIQUEMENT avec du JSON valide, sans texte avant ou après. Commence directement par { et termine par }.

Format JSON requis :

{
  "file": "chemin/du/fichier.php",
  "solid_ok": true,
  "problems": [],
  "score": 85
}

ou si problèmes détectés :

{
  "file": "chemin/du/fichier.php",
  "solid_ok": false,
  "problems": [
    {
      "principle": "SRP",
      "severity": "major",
      "summary": "La classe a plusieurs responsabilités",
      "suggestion": "Séparer en plusieurs classes",
      "line": 42
    }
  ],
  "score": 60
}

FICHIER: %s

CODE:
%s
PROMPT;

    return sprintf($basePrompt, $filePath, $fileContent);
}

// -----------------------------------------------------------------------------
// Main
// -----------------------------------------------------------------------------

$model   = $argv[1] ?? 'llama3.2';
$baseRef = $argv[2] ?? 'HEAD^';
$headRef = $argv[3] ?? 'HEAD';

println("🔍 Analyse SOLID avec Ollama (modèle: {$model})");
println("📊 Comparaison: {$baseRef}..{$headRef}");
println();

println("Recherche des fichiers PHP modifiés...");
$diffOutput = runCommand(sprintf(
    'git diff --name-only %s %s',
    escapeshellarg($baseRef),
    escapeshellarg($headRef)
), true);

$files = array_filter(
    array_map('trim', explode("\n", $diffOutput)),
    static fn(string $f): bool => $f !== '' && str_ends_with($f, '.php')
);

if (empty($files)) {
    println("✅ Aucun fichier PHP modifié, analyse SOLID ignorée.");
    exit(0);
}

println("📝 Fichiers PHP modifiés détectés:");
foreach ($files as $f) {
    println("  - {$f}");
}
println();

$workspace  = getenv('GITHUB_WORKSPACE') ?: getcwd();
$reportDir  = $workspace . '/.github/solid-reports';
@mkdir($reportDir, 0777, true);
$reportFile = $reportDir . '/solid-report.md';

$report = "# 🔍 Rapport d'analyse SOLID\n\n";
$report .= "Analyse effectuée avec le modèle **{$model}** sur les fichiers PHP modifiés.\n\n";

$failed = false;

foreach ($files as $file) {
    println(str_repeat('━', 78));
    println("📄 Analyse de: {$file}\n");

    if (!is_file($file)) {
        println("⚠️  Fichier supprimé, ignoré.\n");
        continue;
    }

    $code = file_get_contents($file);
    if ($code === false) {
        println("⚠️  Impossible de lire le fichier, ignoré.\n");
        continue;
    }

    println("🤖 Interrogation de l'IA...");
    $prompt   = buildPrompt($file, $code);
    $rawReply = callOllama($model, $prompt);

    $json = extractJson($rawReply);
    if ($json === null) {
        println("⚠️  Impossible d'extraire un JSON valide pour {$file}.");
        println("Réponse brute (extrait) :");
        println(implode("\n", array_slice(explode("\n", stripAnsi($rawReply)), 0, 30)));
        println();
        continue;
    }

    println("📊 Résultat de l'analyse (JSON) :");
    println(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    println();

    $solidOk       = (bool)($json['solid_ok'] ?? false);
    $score         = (int)($json['score'] ?? 0);
    $problems      = is_array($json['problems'] ?? null) ? $json['problems'] : [];
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
        $line      = $p['line'] ?? null;

        $report .= "### {$principle} - {$severity}\n\n";
        if ($line !== null) {
            $report .= "**Ligne**: {$line}\n\n";
        }
        $report .= "**Problème**: {$summary}\n\n";
        $report .= "**Suggestion**: {$suggest}\n\n";
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
