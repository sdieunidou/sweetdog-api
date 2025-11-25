<?php

declare(strict_types=1);

namespace SolidAi;

/**
 * Represents a single problem entry in the report.
 * This mirrors the raw JSON structure enough for reporting.
 */
final class SolidProblem
{
    /**
     * @param string[] $refactorSteps
     */
    public function __construct(
        public readonly string $principle,
        public readonly string $severity,
        public readonly string $summary,
        public readonly string $suggestion,
        public readonly ?int $line,
        public readonly array $refactorSteps,
    ) {}
}

/**
 * Represents all problems for a given file.
 */
final class FileProblems
{
    /**
     * @param SolidProblem[] $problems
     */
    public function __construct(
        public readonly string $file,
        public readonly array $problems,
    ) {}

    public function hasProblems(): bool
    {
        return [] !== $this->problems;
    }

    public function countProblems(): int
    {
        return \count($this->problems);
    }

    public function countMajorProblems(): int
    {
        return \count(array_filter(
            $this->problems,
            static fn (SolidProblem $p): bool => 'major' === strtolower($p->severity)
        ));
    }
}

/**
 * Reads the normalized JSON array and converts it to FileProblems objects.
 */
final class SolidReportDataReader
{
    /**
     * @return FileProblems[]
     */
    public function read(string $jsonFile): array
    {
        if (!is_file($jsonFile)) {
            throw new \RuntimeException("Input file not found: {$jsonFile}");
        }

        $raw = file_get_contents($jsonFile);
        if (false === $raw) {
            throw new \RuntimeException("Unable to read file: {$jsonFile}");
        }

        $decoded = json_decode($raw, true);
        if (!\is_array($decoded)) {
            throw new \RuntimeException("Invalid JSON format in {$jsonFile}");
        }

        // Expecting: [
        //   {
        //      "file": "src/Foo.php",
        //      "problems": [ { ... }, ... ]
        //   },
        //   ...
        // ]
        $files = [];

        foreach ($decoded as $entry) {
            if (!\is_array($entry)) {
                continue;
            }

            $file = (string) ($entry['file'] ?? '');
            if ('' === $file) {
                continue;
            }

            $problemsRaw = $entry['problems'] ?? [];
            if (!\is_array($problemsRaw)) {
                $problemsRaw = [];
            }

            $problems = [];
            foreach ($problemsRaw as $p) {
                if (!\is_array($p)) {
                    continue;
                }

                $refactorSteps = $p['refactor_steps'] ?? [];
                if (!\is_array($refactorSteps)) {
                    $refactorSteps = [];
                }

                $problems[] = new SolidProblem(
                    principle: (string) ($p['principle'] ?? 'N/A'),
                    severity: (string) ($p['severity'] ?? 'unknown'),
                    summary: (string) ($p['summary'] ?? ''),
                    suggestion: (string) ($p['suggestion'] ?? ''),
                    line: isset($p['line']) ? (int) $p['line'] : null,
                    refactorSteps: array_map('strval', $refactorSteps),
                );
            }

            $files[] = new FileProblems(
                file: $file,
                problems: $problems,
            );
        }

        return $files;
    }
}

/**
 * Generates a markdown report from FileProblems objects.
 */
final class SolidMarkdownReportGenerator
{
    /**
     * @param FileProblems[] $files
     */
    public function generate(array $files): string
    {
        $totalProblems = 0;
        $totalMajor = 0;

        foreach ($files as $file) {
            $totalProblems += $file->countProblems();
            $totalMajor += $file->countMajorProblems();
        }

        if (0 === $totalProblems) {
            // No violations at all -> no report should be generated.
            return '';
        }

        $md = "# 🔍 SOLID Analysis Report (AI)\n\n";
        $md .= "Automatic analysis performed with **inspecta-ai** on modified PHP files (`src/` and `tests/`).\n\n";
        $md .= "- Total problems: **{$totalProblems}**\n";
        $md .= "- Major problems: **{$totalMajor}**\n\n";
        $md .= "---\n";

        foreach ($files as $file) {
            if (!$file->hasProblems()) {
                continue;
            }

            $md .= "\n## 📄 {$file->file}\n\n";

            foreach ($file->problems as $problem) {
                $principle = $problem->principle;
                $severity = $problem->severity;
                $line = $problem->line;

                $md .= "### {$principle} - {$severity}\n\n";

                if (null !== $line && $line > 0) {
                    $md .= "- **Line**: {$line}\n";
                }

                if ('' !== $problem->summary) {
                    $md .= "- **Problem**: {$problem->summary}\n";
                }

                if ('' !== $problem->suggestion) {
                    $md .= "- **Suggestion**: {$problem->suggestion}\n";
                }

                if ([] !== $problem->refactorSteps) {
                    $md .= "- **Suggested refactor steps**:\n";
                    foreach ($problem->refactorSteps as $step) {
                        $md .= "  - {$step}\n";
                    }
                }

                $md .= "\n";
            }
        }

        return $md;
    }
}

/**
 * Orchestrates reading, generating and writing the markdown report.
 */
final class SolidReportGenerator
{
    public function __construct(
        private readonly SolidReportDataReader $reader,
        private readonly SolidMarkdownReportGenerator $markdownGenerator,
    ) {}

    public function generateReport(string $jsonFile, string $outputFile): bool
    {
        $files = $this->reader->read($jsonFile);

        // Generate markdown (may return empty string if no violations)
        $markdown = $this->markdownGenerator->generate($files);

        if ('' === $markdown) {
            // No report to generate – no file should be created.
            return false;
        }

        $dir = \dirname($outputFile);
        if (!\is_dir($dir)) {
            \mkdir($dir, 0777, true);
        }

        \file_put_contents($outputFile, $markdown);

        return true;
    }
}

// -----------------------------------------------------------------------------
// CLI execution
// -----------------------------------------------------------------------------

if ($argc < 3) {
    fwrite(STDERR, "Usage: php generate_solid_report.php <normalized_json_file> <output_markdown_file>\n");

    exit(1);
}

$input = $argv[1];
$output = $argv[2];

try {
    $generator = new SolidReportGenerator(
        new SolidReportDataReader(),
        new SolidMarkdownReportGenerator(),
    );

    $hasReport = $generator->generateReport($input, $output);

    if (!$hasReport) {
        // No violations => no report file => still success (exit 0)
        exit(0);
    }
} catch (\RuntimeException $e) {
    fwrite(STDERR, 'Error while generating SOLID report: '.$e->getMessage()."\n");

    exit(1);
}

exit(0);
