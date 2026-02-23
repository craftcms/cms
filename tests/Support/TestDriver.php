<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

interface TestDriver
{
    /**
     * Run an Artisan command and return the output lines and exit code.
     *
     * @return array{output: list<string>, exitCode: int}
     */
    public function artisan(string $command): array;
}
