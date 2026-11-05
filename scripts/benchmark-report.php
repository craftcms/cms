<?php

declare(strict_types=1);

/**
 * Prints a Markdown table of benchmark results, compared with a baseline when one is given.
 *
 * Usage: php scripts/benchmark-report.php <results.xml> [<baseline.xml>]
 *
 * Both files come from `scripts/benchmark.sh --dump-file=<file>`. A benchmark is flagged when it's more than
 * {@see THRESHOLD} slower than the baseline and the change is larger than {@see NOISE} times their combined standard
 * deviation, so a small benchmark's jitter doesn't count as a regression.
 */

use PhpBench\Model\Variant;
use PhpBench\Serializer\XmlDecoder;

require dirname(__DIR__).'/vendor/autoload.php';

[, $resultsFile, $baselineFile] = $argv + [2 => null];

/** The relative slowdown that counts as a regression */
const THRESHOLD = 0.10;

/** How many combined standard deviations a slowdown must exceed to count as more than noise */
const NOISE = 3;

/**
 * @return array<string, Variant> Variants keyed by benchmark, subject and parameter set
 */
function variants(string $file): array
{
    $variants = [];

    foreach ((new XmlDecoder)->decodeFiles([$file])->getSuites() as $suite) {
        foreach ($suite->getVariants() as $variant) {
            $subject = $variant->getSubject();
            $key = $subject->getBenchmark()->getClass().'::'.$subject->getName().'#'.$variant->getParameterSet()->getName();
            $variants[$key] = $variant;
        }
    }

    return $variants;
}

/**
 * Returns the mode of a variant's times in milliseconds, or null if it failed.
 */
function modeMs(?Variant $variant): ?float
{
    return $variant && ! $variant->hasErrorStack() ? $variant->getStats()->getMode() / 1000 : null;
}

/**
 * Returns the standard deviation of a variant's times in milliseconds.
 */
function stdevMs(Variant $variant): float
{
    return $variant->getStats()->getStdev() / 1000;
}

/**
 * Returns whether a variant is slower than its baseline by more than both the threshold and the noise.
 */
function isRegression(Variant $variant, Variant $baseline): bool
{
    $change = modeMs($variant) - modeMs($baseline);

    return $change > modeMs($baseline) * THRESHOLD
        && $change > NOISE * sqrt(stdevMs($variant) ** 2 + stdevMs($baseline) ** 2);
}

/**
 * Returns a benchmark's name: its parameter set, or its subject's name as words, such as “Cp config” for `benchCpConfig`.
 */
function name(Variant $variant): string
{
    return $variant->getParameterSet()->getName()
        ?: ucfirst(strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', substr($variant->getSubject()->getName(), 5)))));
}

function formatMs(?float $ms): string
{
    return match (true) {
        $ms === null => 'failed',
        $ms < 10 => sprintf('%.2f ms', $ms),
        default => sprintf('%.0f ms', $ms),
    };
}

echo "<!-- craft-benchmarks -->\n### Benchmarks\n\n";

if (! is_file($resultsFile)) {
    echo "The benchmarks didn't run. See the workflow's log.\n";
    exit(0);
}

$results = variants($resultsFile);
$baseline = $baselineFile && is_file($baselineFile) ? variants($baselineFile) : null;

if ($baseline === null) {
    echo "There are no results from the base branch to compare with.\n\n| Benchmark | Time |\n|---|--:|\n";

    foreach ($results as $variant) {
        printf("| %s | %s |\n", name($variant), formatMs(modeMs($variant)));
    }

    exit(0);
}

$rows = [];
$slower = 0;

foreach ($results as $key => $variant) {
    $now = modeMs($variant);
    $before = modeMs($baseline[$key] ?? null);
    $change = $now !== null && $before !== null ? $now - $before : null;
    $flagged = $now === null || ($change !== null && isRegression($variant, $baseline[$key]));
    $slower += (int) $flagged;

    $rows[] = sprintf(
        '| %s%s | %s | %s | %s | %s |',
        $flagged ? '⚠️ ' : '',
        name($variant),
        isset($baseline[$key]) ? formatMs($before) : 'new',
        formatMs($now),
        $change === null ? '' : sprintf('%+.1f ms (%+.0f%%)', $change, $change / $before * 100),
        $now === null ? '' : sprintf('±%.1f%%', $variant->getStats()->getRstdev()),
    );
}

$threshold = THRESHOLD * 100;
$noise = NOISE;

echo $slower === 0
    ? "No benchmark is more than {$threshold}% slower than the base branch.\n\n"
    : "⚠️ $slower ".($slower === 1 ? 'benchmark is' : 'benchmarks are')." more than {$threshold}% slower than the base branch, or failed.\n\n";

echo "| Benchmark | Base | This PR | Change | Spread |\n|---|--:|--:|--:|--:|\n";
echo implode("\n", $rows)."\n\n";
echo "<sub>The mode of each benchmark's runs, with the base and this pull request measured on the same runner. Spread is the relative standard deviation of this pull request's runs. A benchmark is flagged when it's more than {$threshold}% slower and the change is more than {$noise}× their combined standard deviation.</sub>\n";
