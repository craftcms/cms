<?php

declare(strict_types=1);

/**
 * Prints a Markdown table of benchmark results, compared with a baseline when one is given.
 *
 * Usage: php scripts/benchmark-report.php <results.xml> [<baseline.xml>]
 *
 * Both files come from `scripts/benchmark.sh --dump-file=<file>`. A benchmark is marked when it's slower than the
 * baseline by more than the limit in its `#[Bench\Assert]` attribute.
 */

use PhpBench\Attributes\Assert;
use PhpBench\Model\Variant;
use PhpBench\Serializer\XmlDecoder;

require dirname(__DIR__).'/vendor/autoload.php';

[, $resultsFile, $baselineFile] = $argv + [2 => null];

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
 * Returns the limit, in milliseconds, of a subject's `#[Bench\Assert]` attribute.
 */
function limitMs(Variant $variant): ?float
{
    $subject = $variant->getSubject();
    $class = new ReflectionClass($subject->getBenchmark()->getClass());
    $attributes = [
        ...($class->hasMethod($subject->getName()) ? $class->getMethod($subject->getName())->getAttributes(Assert::class) : []),
        ...$class->getAttributes(Assert::class),
    ];

    foreach ($attributes as $attribute) {
        if (preg_match('/\+\/- ([\d.]+) milliseconds/', $attribute->newInstance()->expression, $match)) {
            return (float) $match[1];
        }
    }

    return null;
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
    $limit = limitMs($variant);
    $change = $now !== null && $before !== null ? $now - $before : null;
    $flagged = $now === null || ($change !== null && $limit !== null && $change > $limit);
    $slower += (int) $flagged;

    $rows[] = sprintf(
        '| %s%s | %s | %s | %s | %s |',
        $flagged ? '⚠️ ' : '',
        name($variant),
        isset($baseline[$key]) ? formatMs($before) : 'new',
        formatMs($now),
        $change === null ? '' : sprintf('%+.1f ms (%+.0f%%)', $change, $change / $before * 100),
        $limit === null ? '' : sprintf('+%s ms', $limit),
    );
}

echo $slower === 0
    ? "No benchmark is slower than the base branch by more than its limit.\n\n"
    : "⚠️ $slower ".($slower === 1 ? 'benchmark is' : 'benchmarks are').' slower than the base branch by more than '.($slower === 1 ? 'its' : 'their')." limit, or failed.\n\n";

echo "| Benchmark | Base | This PR | Change | Limit |\n|---|--:|--:|--:|--:|\n";
echo implode("\n", $rows)."\n\n";
echo "<sub>The mode of each benchmark's runs, with the base and this pull request measured on the same runner. Page times can vary by about 15% between runs.</sub>\n";
