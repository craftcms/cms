<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\helpers\FileHelper;
use CraftCms\Aliases\Aliases;
use CraftCms\Yii2Adapter\Container;
use yii\BaseYii;

/**
 * @inheritdoc
 */
class Yii extends BaseYii
{
    /**
     * @var string[] Record of all registered aliases and the paths they map to.
     */
    private static $_aliasPaths = [];

    /**
     * @var bool Whether [[$aliasPaths]] has changed since it was last sorted.
     */
    private static $_aliasesChanged = false;

    public static function getAlias($alias, $throwException = true)
    {
        try {
            return Aliases::get($alias);
        } catch (Throwable $e) {
            if ($throwException) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public static function setAlias($alias, $path): void
    {
        Aliases::set($alias, $path);

        self::$_aliasPaths[$alias] = FileHelper::normalizePath($path);
        self::$_aliasesChanged = true;
    }

    public static function getRootAlias($alias): string|false
    {
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        $aliases = Aliases::getAll();

        if (isset($aliases[$root])) {
            if (is_string($aliases[$root])) {
                return $root;
            }

            foreach ($aliases[$root] as $name => $path) {
                if (str_starts_with($alias . '/', $name . '/')) {
                    return $name;
                }
            }
        }

        return false;
    }

    /**
     * Swaps the beginning of a path with the most specific alias we can find, if any.
     *
     * @param string $path
     * @return string
     * @since 3.0.3
     */
    public static function alias(string $path): string
    {
        // Do the alias paths need to be sorted?
        if (self::$_aliasesChanged) {
            $lengths = [];
            foreach (self::$_aliasPaths as $aliasPath) {
                $lengths[] = strlen($aliasPath);
            }
            array_multisort($lengths, SORT_DESC, SORT_NUMERIC, self::$_aliasPaths);
            self::$_aliasesChanged = false;
        }

        $path = FileHelper::normalizePath($path);
        foreach (self::$_aliasPaths as $alias => $aliasPath) {
            if (str_starts_with($path . DIRECTORY_SEPARATOR, $aliasPath . DIRECTORY_SEPARATOR)) {
                return $alias . str_replace('\\', '/', substr($path, strlen($aliasPath)));
            }
        }
        return $path;
    }
}

Yii::$container = new Container();
