<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\Benchmark\BenchCase;

require dirname(__DIR__, 2).'/vendor/autoload.php';

if (! is_file(BenchCase::basePath().'/database/database.sqlite')) {
    fwrite(STDERR, 'No benchmark workbench at '.BenchCase::basePath().". Run scripts/benchmark-setup.sh first.\n");
    exit(1);
}
