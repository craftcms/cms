<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use yii\base\ErrorException;
use yii\base\InvalidParamException;

/**
 * Class FileHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FileHelper extends \yii\helpers\FileHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        // Is this a UNC network share path?
        $isUnc = (strncmp($path, '//', 2) === 0 || strncmp($path, '\\\\', 2) === 0);

        // Normalize the path
        $path = parent::normalizePath($path, $ds);

        // If it is UNC, add those slashes back in front
        if ($isUnc) {
            $path = $ds.$ds.ltrim($path, $ds);
        }

        return $path;
    }

    /**
     * Removes all of a directory’s contents recursively.
     *
     * @param string $dir     the directory to be deleted recursively.
     * @param array  $options options for directory remove. Valid options are:
     *
     * - traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     *
     * @return void
     * @throws InvalidParamException if the dir is invalid.
     * @throws ErrorException in case of failure
     */
    public static function clearDirectory($dir, $options = [])
    {
        if (!is_dir($dir)) {
            throw new InvalidParamException("The dir argument must be a directory: $dir");
        }

        // Copied from [[removeDirectory()]] minus the root directory removal at the end
        if (!($handle = opendir($dir))) {
            return;
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                static::removeDirectory($path, $options);
            } else {
                try {
                    unlink($path);
                } catch (ErrorException $e) {
                    if (DIRECTORY_SEPARATOR === '\\') {
                        // last resort measure for Windows
                        $lines = [];
                        exec("DEL /F/Q \"$path\"", $lines, $deleteError);
                    } else {
                        throw $e;
                    }
                }
            }
        }
        closedir($handle);
    }
}
