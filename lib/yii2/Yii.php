<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

use craft\helpers\FileHelper;
use yii\BaseYii;
use yii\di\Container;

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

    /**
     * @inheritdoc
     */
    public static function setAlias($alias, $path)
    {
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        parent::setAlias($alias, $path);
        self::$_aliasPaths[$alias] = FileHelper::normalizePath($path);
        self::$_aliasesChanged = true;
    }

    /**
     * Swaps the beginning of a path with the most specific alias we can find, if any.
     *
     * @param string $path
     * @return string
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
            if (strpos($path . '/', $aliasPath . '/') === 0) {
                return $alias . substr($path, strlen($aliasPath));
            }
        }
        return $path;
    }
}

Yii::$container = new Container();
