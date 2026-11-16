<?php

declare(strict_types=1);

use Tally\Configuration;

/**
 * Tally counts the benchmarks in tests/Benchmark against the workbench that scripts/benchmark-setup.sh builds.
 *
 * Each case runs in its own PHP process, so compiled scripts are kept in an opcache file cache, as a PHP-FPM worker
 * keeps them in shared memory. Tally runs each case natively before counting it, which fills the cache, so compiling
 * isn't counted. JIT is off because PHP doesn't use the file cache with it, and setup-php turns it on in CI.
 *
 * CI compares a pull request with its base branch rather than with committed numbers, so the benchmarks have no
 * #[Expected] attributes. See .github/workflows/benchmarks.yml.
 */
return Configuration::create()
    ->withPaths([__DIR__.'/tests/Benchmark'])
    ->withIniEntries([
        'opcache.enable_cli=1',
        'opcache.file_cache='.__DIR__.'/.benchmarks/opcache',
        'opcache.jit=disable',
        'opcache.validate_timestamps=1',
        'memory_limit=1G',
        'variables_order=EGPCS',
    ])
    ->withEnvironmentVariables([
        'PATH',
        'HOME',
        'TMPDIR',
        'LANG',
        'LC_ALL',
        'PHPRC',
        'PHP_INI_SCAN_DIR',
        'CRAFT_BENCHMARK_PATH',
    ]);
