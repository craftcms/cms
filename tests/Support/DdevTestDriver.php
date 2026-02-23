<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use RuntimeException;

final readonly class DdevTestDriver implements TestDriver
{
    private string $projectDir;

    public function __construct()
    {
        $projectDir = env('DDEV_PROJECT_DIR');

        if ($projectDir === null || $projectDir === '') {
            throw new RuntimeException(
                'DDEV_PROJECT_DIR must be set in tests/.env when using the "ddev" test driver.',
            );
        }

        $this->projectDir = (string) $projectDir;
    }

    public function artisan(string $command): array
    {
        exec(
            sprintf('cd %s && ddev exec php artisan %s 2>&1', escapeshellarg($this->projectDir), $command),
            $output,
            $exitCode,
        );

        return ['output' => $output, 'exitCode' => $exitCode];
    }
}
