<?php

namespace CraftCms\Cms\Support;

use CraftCms\Aliases\Facades\Aliases;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
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

    /**
     * Checks if a string references an environment variable (`$VARIABLE_NAME`)
     * and/or an alias (`@aliasName`), and returns the referenced value.
     *
     * If the string references an environment variable with a value of `true`
     * or `false`, a boolean value will be returned.
     *
     * If the string references an environment variable that’s not defined,
     * `null` will be returned.
     *
     * ---
     *
     * ```php
     * $value1 = Env::parse('$SMTP_PASSWORD');
     * $value2 = Env::parse('@webroot');
     * ```
     *
     * @return string|bool|null The parsed value, or the original value if it didn’t
     *                          reference an environment variable and/or alias.
     */
    public static function parse(?string $value): bool|string|null
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\$(\w+)(\/.*)?/', $value, $matches)) {
            $env = Env::get($matches[1]);

            if ($env === null) {
                // No env var or constant is defined here by that name
                return null;
            }

            $value = $env.($matches[2] ?? '');
        }

        if (str_starts_with($value, '@')) {
            try {
                $value = Aliases::get($value);
            } catch (InvalidArgumentException) {
            }
        }

        return $value;
    }

    /**
     * Checks if a string references an environment variable (`$VARIABLE_NAME`) and returns the referenced
     * boolean value, or `null` if a boolean value can’t be determined.
     *
     * ---
     *
     * ```php
     * $status = Env::parseBoolean('$SYSTEM_STATUS') ?? false;
     * ```
     */
    public static function parseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 0 || $value === 1) {
            return (bool) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = static::parse($value);

        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }
}
