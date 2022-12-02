<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\base\InvalidArgumentException;

/**
 * Composer helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Composer
{
    /**
     * Returns the PSR-4 autolaoding array from a given `composer.json` file.
     *
     * @param string $file
     * @return array
     * @throws InvalidArgumentException if the file doesn’t exist or there was a problem JSON-decoding it
     */
    public static function autoloadConfigFromFile(string $file): array
    {
        $config = Json::decodeFromFile($file);
        $autoload = $config['autoload']['psr-4'] ?? [];

        // Make sure each of the keys ends in "\"
        return array_combine(
            array_map(fn(string $namespace) => StringHelper::ensureRight($namespace, '\\'), array_keys($autoload)),
            array_values($autoload),
        );
    }

    /**
     * Returns whether a directory path could be autoloaded from the given `composer.json` file.
     *
     * @param string $dir
     * @param string $composerFile
     * @param array|null $existingRoot an existing autoload root’s namespace and directory, if there is one which
     * contains `$dir`.
     *
     * Note that this will be set regardless of whether the directory is actually autoloadable. If it’s set and `false`
     * is returned, then it’s set to a conflicting autoload root.
     *
     * @param string|null $reason why `false` was returned
     * @return bool whether the directory can be autoloaded. `false` will be returned if:
     *
     * - the directory lives *above* the directory containing `composer.json`
     * - the directory lives within an existing autoload root, but its subpath contains segments that wouldn’t be valid
     *   in a PHP namespace. If this is the case, `$existingRoot` will be set to the existing autoload root.
     *
     * @throws InvalidArgumentException if the `composer.json` file doesn’t exist or there was a problem JSON-decoding it
     */
    public static function couldAutoload(
        string $dir,
        string $composerFile,
        ?array &$existingRoot = null,
        ?string &$reason = null,
    ): bool {
        $dir = FileHelper::absolutePath(Craft::getAlias($dir), ds: '/');
        $composerFile = FileHelper::absolutePath(Craft::getAlias($composerFile), ds: '/');
        $composerDir = dirname($composerFile);

        if ($dir !== $composerDir && !FileHelper::isWithin($dir, $composerDir)) {
            $reason = "The directory must be within `$composerDir/`.";
            return false;
        }

        $autoload = static::autoloadConfigFromFile($composerFile);

        foreach ($autoload as $rootNamespace => $rootPath) {
            $rootPath = FileHelper::absolutePath($rootPath, $composerDir, '/');

            if ($dir === $rootPath || FileHelper::isWithin($dir, $rootPath)) {
                $existingRoot = [$rootNamespace, $rootPath];

                if ($dir !== $rootPath) {
                    // Make sure the entire relative path is namespace-safe
                    $relativePath = FileHelper::relativePath($dir, $rootPath);
                    $relativeNamespace = str_replace('/', '\\', $relativePath);
                    if (!App::validateNamespace($relativeNamespace)) {
                        $invalidNamespace = $rootNamespace . $relativeNamespace;
                        $reason = "That directory would conflict with the existing `$rootNamespace` autoload root (`$invalidNamespace` isn’t a valid PHP namespace).";
                        return false;
                    }
                }

                return true;
            }
        }

        // Leave $existingRoot set to `null` to indicate that there's no existing root
        // but return `true` as it could be autoloaded with a new root
        return true;
    }
}
