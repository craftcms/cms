<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

final readonly class LaravelTestDriver implements TestDriver
{
    public function artisan(string $command): array
    {
        exec(sprintf('php artisan %s 2>&1', $command), $output, $exitCode);

        return ['output' => $output, 'exitCode' => $exitCode];
    }
}
