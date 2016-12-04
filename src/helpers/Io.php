<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\dates\DateTime;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * Class Io
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Io
{
    // Public Methods
    // =========================================================================

    /**
     * Tests whether the given file path exists on the file system.
     *
     * @param string  $path            The path to test
     * @param boolean $caseInsensitive Whether to perform a case insensitive check or not
     * @param boolean $suppressErrors  Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return string|false The resolved path of the file if it exists, otherwise false
     */
    public static function fileExists($path, $caseInsensitive = false, $suppressErrors = false)
    {
        $resolvedPath = static::getRealPath($path, $suppressErrors);

        if ($resolvedPath) {
            if ($suppressErrors ? @is_file($resolvedPath) : is_file($resolvedPath)) {
                return $resolvedPath;
            }
        } else if ($caseInsensitive) {
            $folder = static::getFolderName($path, true, $suppressErrors);
            $files = static::getFolderContents($folder, false, null, false, $suppressErrors);
            $lcaseFilename = StringHelper::toLowerCase($path);

            if (is_array($files) && count($files) > 0) {
                foreach ($files as $file) {
                    if ($suppressErrors ? @is_file($file) : is_file($file)) {
                        if (StringHelper::toLowerCase($file) === $lcaseFilename) {
                            return $file;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Tests whether the given folder path exists on the file system.
     *
     * @param string  $path            The path to test
     * @param boolean $caseInsensitive Whether to perform a case insensitive check or not
     * @param boolean $suppressErrors  Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return string|false The resolved path to the folder if it exists, otherwise false
     */
    public static function folderExists($path, $caseInsensitive = false, $suppressErrors = false)
    {
        $path = static::getRealPath($path, $suppressErrors);

        if ($path) {
            if ($suppressErrors ? @is_dir($path) : is_dir($path)) {
                return $path;
            }

            if ($caseInsensitive) {
                return StringHelper::toLowerCase(static::getFolderName($path, true, $suppressErrors)) === StringHelper::toLowerCase($path);
            }
        }

        return false;
    }

    /**
     * Returns the real filesystem path of the given path.
     *
     * @param string  $path           The path to test
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return string|false The real file or folder path, or `false` if the file doesn’t exist
     */
    public static function getRealPath($path, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);
        $path = $suppressErrors ? @realpath($path) : realpath($path);

        // realpath() should just return false if the file doesn't exist, but seeing one case where
        // it's returning an empty string instead
        if (!$path) {
            return false;
        }

        // Normalize again, because realpath probably screwed things up again.
        return FileHelper::normalizePath($path);
    }

    /**
     * Tests whether the give filesystem path is readable.
     *
     * @param string  $path           The path to test
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return boolean Whether the path is readable
     */
    public static function isReadable($path, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);

        return $suppressErrors ? @is_readable($path) : is_readable($path);
    }

    /**
     * Tests file and folder write-ability by attempting to create a temp file on the filesystem. PHP's is_writable has
     * problems (especially on Windows). {@see https://bugs.php.net/bug.php?id=27609} and
     * {@see https://bugs.php.net/bug.php?id=30931}.
     *
     * @param string  $path           The path to test
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return boolean Whether the path is writable
     */
    public static function isWritable($path, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);

        if (static::folderExists($path, false, $suppressErrors)) {
            return static::isWritable($path.'/'.uniqid(mt_rand()).'.tmp', $suppressErrors);
        }

        // Check tmp file for read/write capabilities
        $rm = static::fileExists($path, false, $suppressErrors);
        $f = @fopen($path, 'a');

        if ($f === false) {
            return false;
        }

        @fclose($f);

        if (!$rm) {
            @unlink($path);
        }

        return true;
    }

    /**
     * Will return the folder name of the given path either as the full path or
     * only the single top level folder.
     *
     * @param string  $path           The path to test
     * @param boolean $fullPath       Whether to include the full path in the return results or the top level folder only
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return string The folder name
     */
    public static function getFolderName($path, $fullPath = true, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);

        if ($fullPath) {
            return FileHelper::normalizePath($suppressErrors ? @pathinfo($path, PATHINFO_DIRNAME) : pathinfo($path, PATHINFO_DIRNAME));
        }

        if ($suppressErrors ? !@is_dir($path) : !is_dir($path)) {
            // Chop off the file
            $path = $suppressErrors ? @pathinfo($path, PATHINFO_DIRNAME) : pathinfo($path, PATHINFO_DIRNAME);
        }

        return $suppressErrors ? @pathinfo($path, PATHINFO_BASENAME) : pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Returns the file extension for the given path.  If there is not one, then $default is returned instead.
     *
     * @param string      $path           The path to test
     * @param null|string $default        If the file has no extension, this one will be returned by default
     * @param boolean     $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return string The file extension
     */
    public static function getExtension($path, $default = null, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);
        $extension = $suppressErrors ? @pathinfo($path, PATHINFO_EXTENSION) : pathinfo($path, PATHINFO_EXTENSION);

        if ($extension) {
            return $extension;
        }

        return $default;
    }

    /**
     * Returns the contents of a folder as an array of file and folder paths, or false if the folder does not exist or
     * is not readable.
     *
     * @param string          $path               The path to test
     * @param boolean         $recursive          Whether to do a recursive folder search
     * @param string|string[] $filter             The filter to use when performing the search
     * @param boolean         $includeHiddenFiles Whether to include hidden files (that start with a .) in the results
     * @param boolean         $suppressErrors     Whether to suppress any PHP Notices/Warnings/Errors (usually permissions
     *                                            related)
     *
     * @return string[]|false An array of file and folder paths, or false if the folder does not exist or is not readable
     */
    public static function getFolderContents($path, $recursive = true, $filter = null, $includeHiddenFiles = false, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);

        if (static::folderExists($path, false, $suppressErrors) && static::isReadable($path, $suppressErrors)) {
            if (($contents = static::_folderContents($path, $recursive, $filter, $includeHiddenFiles, $suppressErrors)) !== false) {
                return $contents;
            }

            Craft::warning('Tried to read the file contents at '.$path.' and could not.', __METHOD__);

            return false;
        }

        return false;
    }

    /**
     * Will create a file on the file system at the given path and return a [[File]] object or false if we don't
     * have write permissions.
     *
     * @param string  $path           The path of the file to create
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return boolean Whether the file was created successfully
     */
    public static function createFile($path, $suppressErrors = false)
    {
        $path = FileHelper::normalizePath($path);

        if (!static::fileExists($path, false, $suppressErrors)) {
            if (($handle = $suppressErrors ? @fopen($path, 'w') : fopen($path, 'w')) === false) {
                Craft::error('Tried to create a file at '.$path.', but could not.', __METHOD__);

                return false;
            }

            @fclose($handle);

            return true;
        }

        return false;
    }

    /**
     * Will create a folder on the file system at the given path and return a [[Folder]] object or false if we don't
     * have write permissions.
     *
     * @param string  $path           The path of the file to create
     * @param integer $permissions    The permissions to set the folder to
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related)
     *
     * @return boolean Whether the folder was created successfully
     */
    public static function createFolder($path, $permissions = null, $suppressErrors = false)
    {
        if ($permissions == null) {
            $permissions = Craft::$app->getConfig()->get('defaultFolderPermissions');
        }

        $path = FileHelper::normalizePath($path);

        if (!static::folderExists($path, false, $suppressErrors)) {
            $oldumask = $suppressErrors ? @umask(0) : umask(0);

            if ($suppressErrors ? !@mkdir($path, $permissions, true) : !mkdir($path, $permissions, true)) {
                Craft::error('Tried to create a folder at '.$path.', but could not.', __METHOD__);

                return false;
            }

            // Because setting permission with mkdir is a crapshoot.
            $suppressErrors ? @chmod($path, $permissions) : chmod($path, $permissions);
            $suppressErrors ? @umask($oldumask) : umask($oldumask);

            return true;
        }

        Craft::warning('Tried to create a folder at '.$path.', but the folder already exists.', __METHOD__);

        return false;
    }

    /**
     * Returns the path to the parent folder of a given path.
     *
     * @param string $path The starting point
     *
     * @return string|false The parent folder’s path, or false if $path is the root path
     */
    public static function getParentFolderPath($path)
    {
        $path = FileHelper::normalizePath($path);
        $parentPath = dirname($path);

        // Was this already the root path?
        if ($parentPath == $path || $parentPath == '.') {
            return false;
        }

        return $parentPath;
    }

    /**
     * Get a temporary file path.
     *
     * @param string $extension The extension to use (defaults to "tmp")
     *
     * @return string The temporary file path
     * @throws Exception
     */
    public static function getTempFilePath($extension = "tmp")
    {
        $extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
        $fileName = uniqid('craft', true).'.'.$extension;
        $path = Craft::$app->getPath()->getTempPath().'/'.$fileName;

        if (!static::createFile($path)) {
            throw new Exception('Could not create temp file: '.$path);
        }

        return static::getRealPath($path);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string  $path
     * @param boolean $recursive
     * @param null    $filter
     * @param boolean $includeHiddenFiles
     * @param boolean $suppressErrors Whether to suppress any PHP Notices/Warnings/Errors (usually permissions related).
     *
     * @return string[]
     */
    private static function _folderContents($path, $recursive = false, $filter = null, $includeHiddenFiles = false, $suppressErrors = false)
    {
        $descendants = [];

        $path = static::getRealPath($path, $suppressErrors);

        if ($filter !== null) {
            if (is_string($filter)) {
                $filter = [$filter];
            }
        }

        if (($contents = $suppressErrors ? @scandir($path) : scandir($path)) !== false) {
            foreach ($contents as $key => $item) {
                $fullItem = $path.'/'.$item;
                $contents[$key] = $fullItem;

                if ($item == '.' || $item == '..') {
                    continue;
                }

                if (!$includeHiddenFiles) {
                    // If it's hidden, skip it.
                    if (isset($item[0]) && $item[0] == '.') {
                        continue;
                    }
                }

                if (static::_filterPassed($contents[$key], $filter)) {
                    if (static::fileExists($contents[$key], false, $suppressErrors)) {
                        $descendants[] = FileHelper::normalizePath($contents[$key]);
                    } else if (static::folderExists($contents[$key], false, $suppressErrors)) {
                        $descendants[] = FileHelper::normalizePath($contents[$key]);
                    }
                }

                if (static::folderExists($contents[$key], false, $suppressErrors) && $recursive) {
                    $descendants = array_merge($descendants, static::_folderContents($contents[$key], $recursive, $filter, $includeHiddenFiles, $suppressErrors));
                }
            }
        } else {
            Craft::error('Unable to get folder contents for “'.$path.'”.', __METHOD__);
        }

        return $descendants;
    }

    /**
     * Applies an array of filter rules to the string representing the file path. Used internally by [[dirContents]]
     * method.
     *
     * @param string $str    String representing file path to be filtered
     * @param array  $filter An array of filter rules, where each rule is a string, supposing that the string starting
     *                       with '/' is a regular expression. Any other string treated as an extension part of the given
     *                       filepath (eg. file extension)
     *
     * @return boolean Whether the supplied string matched one of the filter rules
     */
    private static function _filterPassed($str, $filter)
    {
        $passed = false;

        if ($filter !== null) {
            foreach ($filter as $rule) {
                $passed = (bool)preg_match('/'.$rule.'/', $str);

                if ($passed) {
                    break;
                }
            }
        } else {
            $passed = true;
        }

        return $passed;
    }
}
