<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Attributes\EnvName;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use ReflectionProperty;
use RuntimeException;

final class Env extends \Illuminate\Support\Env
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

    #[\Override]
    public static function get($key, $default = null): mixed
    {
        $value = parent::get($key, $default);

        if (is_string($value)) {
            // parse nested variables
            $value = self::parseNested($value);
        }

        return $value;
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
     * @return string|null The parsed value, or the original value if it didn’t
     *                     reference an environment variable and/or alias.
     */
    public static function parse(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // …${VAR}…
        $value = self::parseNested($value);

        // …/$VAR/…
        $value = preg_replace_callback(
            '/(?<=^|\/)\$(\w+)(?=$|\/)?/',
            fn ($m) => self::get($m[1]), $value
        );

        if ($value === '') {
            return null;
        }

        if (str_starts_with((string) $value, '@') && $alias = Aliases::get($value)) {
            $value = $alias;
        }

        return $value;
    }

    private static function parseNested(string $value): string
    {
        return preg_replace_callback('/\$\{(\w+)}/', fn (array $m) => self::get($m[1]), $value);
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

        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = self::parse($value);

        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Returns a config array for a given class, based on any environment variables or PHP constants named based on its
     * public properties.
     *
     * Environment variable/PHP constant names must be capitalized, SNAKE_CASED versions of the object’s property names,
     * possibly with a given prefix.
     *
     * For example, if an object has a `fooBar` property, and `X`/`X_` is passed as the prefix, the resulting array
     * may contain a `fooBar` key set to an `X_FOO_BAR` environment variable value, if it exists.
     *
     * @param  class-string  $class  The class name
     * @param  string|null  $prefix  The environment variable name prefix
     *
     * @phpstan-return array<string, mixed>
     */
    public static function config(string $class, ?string $prefix = null): array
    {
        $prefix = when(is_null($prefix), '', Str::finish($prefix, '_'));

        return Utils::getPublicReflectionProperties($class)
            ->mapWithKeys(function (ReflectionProperty $property) use ($prefix) {
                $envName = str($property->getName())->snake()->upper()->value();

                if ($attribute = Arr::first($property->getAttributes(EnvName::class))) {
                    $envName = $attribute->newInstance();
                    $envName = $envName->name;
                }

                return [$property->getName() => self::get($prefix.$envName)];
            })
            ->filter(fn (mixed $value) => ! is_null($value))
            ->all();
    }
}
