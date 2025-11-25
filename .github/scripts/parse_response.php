<?php

declare(strict_types=1);

/**
 * This script:
 *   1. Reads a raw file containing multiple consecutive JSON objects from inspecta-ai
 *   2. Extracts each individual JSON object safely
 *   3. Produces a valid JSON array: [ {...}, {...}, ... ]
 *
 * Usage:
 *   php parse_response.php input_raw.json output_parsed.json
 */

namespace SolidAi;

/**
 * Extracts multiple JSON objects from a raw stream.
 * This is robust even when inspecta outputs `{...}{...}{...}` without separators.
 */
final class MultiJsonExtractor
{
    /**
     * @return array<int,array<mixed>> A list of decoded JSON objects
     */
    public function extract(string $raw): array
    {
        $raw = trim($raw);
        if ('' === $raw) {
            return [];
        }

        $len = strlen($raw);
        $i = 0;
        $objects = [];

        while ($i < $len) {
            // Skip leading whitespace
            while ($i < $len && ctype_space($raw[$i])) {
                ++$i;
            }
            if ($i >= $len) {
                break;
            }

            // Search for the beginning of a JSON object
            if ('{' !== $raw[$i]) {
                ++$i;

                continue;
            }

            $start = $i;
            $depth = 0;
            $inString = false;
            $escape = false;

            for (; $i < $len; ++$i) {
                $ch = $raw[$i];

                if ($escape) {
                    $escape = false;

                    continue;
                }

                if ('\\' === $ch) {
                    $escape = true;

                    continue;
                }

                if ('"' === $ch) {
                    $inString = !$inString;

                    continue;
                }

                if ($inString) {
                    continue;
                }

                if ('{' === $ch) {
                    ++$depth;
                } elseif ('}' === $ch) {
                    --$depth;

                    if (0 === $depth) {
                        // Complete JSON object found
                        $jsonString = substr($raw, $start, $i - $start + 1);

                        $decoded = json_decode($jsonString, true);
                        if (JSON_ERROR_NONE !== json_last_error()) {
                            throw new \RuntimeException(
                                'Invalid JSON object detected: '.json_last_error_msg()
                                ."\nChunk: ".$jsonString
                            );
                        }

                        $objects[] = $decoded;
                        ++$i; // Move after the closing brace

                        break;
                    }
                }
            }
        }

        return $objects;
    }
}

/**
 * Writes a raw list of JSON objects into a valid JSON array file.
 */
final class JsonArrayWriter
{
    /**
     * @param array<int,array<mixed>> $objects
     */
    public function write(string $outputFile, array $objects): void
    {
        $json = json_encode($objects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode JSON array: '.json_last_error_msg());
        }

        $dir = dirname($outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($outputFile, $json);
    }
}

// -----------------------------------------------------------------------------
// CLI Execution
// -----------------------------------------------------------------------------

if ($argc < 3) {
    fwrite(STDERR, "Usage: php parse_response.php <input_raw_file> <output_json_file>\n");

    exit(1);
}

$input = $argv[1];
$output = $argv[2];

if (!is_file($input)) {
    fwrite(STDERR, "Input file not found: {$input}\n");

    exit(1);
}

$raw = file_get_contents($input);
if (false === $raw) {
    fwrite(STDERR, "Unable to read input file: {$input}\n");

    exit(1);
}

try {
    // Extract objects
    $extractor = new MultiJsonExtractor();
    $objects = $extractor->extract($raw);

    if ([] === $objects) {
        fwrite(STDERR, "No valid JSON objects found in inspecta-ai output.\n");

        exit(1);
    }

    // Write valid JSON array
    $writer = new JsonArrayWriter();
    $writer->write($output, $objects);
} catch (\RuntimeException $e) {
    fwrite(STDERR, 'Error while parsing inspecta-ai response: '.$e->getMessage()."\n");

    exit(1);
}

exit(0);
