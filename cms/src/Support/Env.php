<?php

namespace CraftCms\Cms\Support;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class Env extends \Illuminate\Support\Env
{
    /**
     * @todo: This can be removed once Laravel releases https://github.com/laravel/framework/commit/d5b1e5fca50d25c1d4dc463eb93068c39593aa3a
     */
    public static function extend(Closure $callback, ?string $name = null): void
    {
        parent::extend($callback, $name);

        static::$repository = null;
    }

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
