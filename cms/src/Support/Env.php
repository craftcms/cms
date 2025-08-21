<?php

namespace CraftCms\Cms\Support;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * @since 6.0.0
 */
class Env extends \Illuminate\Support\Env
{
    /**
     * Remove a single key from the environment file.
     *
     *
     * @throws RuntimeException
     * @throws FileNotFoundException
     */
    public static function removeVariable(string $key, string $pathToFile): void
    {
        $filesystem = new Filesystem;

        if ($filesystem->missing($pathToFile)) {
            throw new RuntimeException("The file [{$pathToFile}] does not exist.");
        }

        $envContent = $filesystem->get($pathToFile);

        $lines = explode(PHP_EOL, $envContent);
        $lines = array_filter($lines, fn ($line) => ! str_starts_with($line, $key.'='));

        $filesystem->put($pathToFile, implode(PHP_EOL, $lines));
    }
}
