<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use ZipArchive;

/**
 * Class FileHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FileHelper extends \yii\helpers\FileHelper
{
    /**
     * @inheritdoc
     */
    public static $mimeMagicFile = '@app/config/mimeTypes.php';

    /**
     * @var bool Whether file locks can be used when writing to files.
     * @see useFileLocks()
     */
    private static $_useFileLocks;

    /**
     * @inheritdoc
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        // Is this a UNC network share path?
        $isUnc = (strpos($path, '//') === 0 || strpos($path, '\\\\') === 0);

        // Normalize the path
        $path = parent::normalizePath($path, $ds);

        // If it is UNC, add those slashes back in front
        if ($isUnc) {
            $path = $ds . $ds . ltrim($path, $ds);
        }

        return $path;
    }

    /**
     * @inheritdoc
     */
    public static function copyDirectory($src, $dst, $options = [])
    {
        if (!isset($options['fileMode'])) {
            $options['fileMode'] = Craft::$app->getConfig()->getGeneral()->defaultFileMode;
        }

        if (!isset($options['dirMode'])) {
            $options['dirMode'] = Craft::$app->getConfig()->getGeneral()->defaultDirMode;
        }

        parent::copyDirectory($src, $dst, $options);
    }

    /**
     * @inheritdoc
     */
    public static function createDirectory($path, $mode = null, $recursive = true)
    {
        if ($mode === null) {
            $mode = Craft::$app->getConfig()->getGeneral()->defaultDirMode;
        }

        return parent::createDirectory($path, $mode, $recursive);
    }

    /**
     * @inheritdoc
     */
    public static function removeDirectory($dir, $options = [])
    {
        try {
            parent::removeDirectory($dir, $options);
        } catch (ErrorException $e) {
            // Try Symfony's thing as a fallback
            $fs = new Filesystem();

            try {
                $fs->remove($dir);
            } catch (IOException $e2) {
                // throw the original exception instead
                throw $e;
            }
        }
    }

    /**
     * Sanitizes a filename.
     *
     * @param string $filename the filename to sanitize
     * @param array $options options for sanitization. Valid options are:
     * - `asciiOnly`: bool, whether only ASCII characters should be allowed. Defaults to false.
     * - `separator`: string|null, the separator character to use in place of whitespace. defaults to '-'. If set to null, whitespace will be preserved.
     * @return string The cleansed filename
     */
    public static function sanitizeFilename(string $filename, array $options = []): string
    {
        $asciiOnly = $options['asciiOnly'] ?? false;
        $separator = array_key_exists('separator', $options) ? $options['separator'] : '-';
        $disallowedChars = [
            'â€”',
            'â€“',
            '&#8216;',
            '&#8217;',
            '&#8220;',
            '&#8221;',
            '&#8211;',
            '&#8212;',
            '+',
            '%',
            '^',
            '~',
            '?',
            '[',
            ']',
            '/',
            '\\',
            '=',
            '<',
            '>',
            ':',
            ';',
            ',',
            '\'',
            '"',
            '&',
            '$',
            '#',
            '*',
            '(',
            ')',
            '|',
            '~',
            '`',
            '!',
            '{',
            '}'
        ];

        // Replace any control characters in the name with a space.
        $filename = preg_replace("/\\x{00a0}/iu", ' ', $filename);

        // Strip any characters not allowed.
        $filename = str_replace($disallowedChars, '', strip_tags($filename));

        if (Craft::$app->getDb()->getIsMysql()) {
            // Strip emojis
            $filename = StringHelper::replaceMb4($filename, '');
        }

        // Nuke any trailing or leading .-_
        $filename = trim($filename, '.-_');

        $filename = $asciiOnly ? StringHelper::toAscii($filename) : $filename;

        if ($separator !== null) {
            $qSeparator = preg_quote($separator, '/');
            $filename = preg_replace("/[\s{$qSeparator}]+/u", $separator, $filename);
            $filename = preg_replace("/^{$qSeparator}+|{$qSeparator}+$/u", '', $filename);
        }

        return $filename;
    }

    /**
     * Returns whether a given directory is empty (has no files) recursively.
     *
     * @param string $dir the directory to be checked
     * @return bool whether the directory is empty
     * @throws InvalidArgumentException if the dir is invalid
     * @throws ErrorException in case of failure
     */
    public static function isDirectoryEmpty(string $dir): bool
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("The dir argument must be a directory: $dir");
        }

        if (!($handle = opendir($dir))) {
            throw new ErrorException("Unable to open the directory: $dir");
        }

        // It's empty until we find a file
        $empty = true;

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path) || !static::isDirectoryEmpty($path)) {
                $empty = false;
                break;
            }
        }

        closedir($handle);

        return $empty;
    }

    /**
     * Tests whether a file/directory is writable.
     *
     * @param string $path the file/directory path to test
     * @return bool whether the path is writable
     * @throws ErrorException in case of failure
     */
    public static function isWritable(string $path): bool
    {
        // If it's a directory, test on a temp sub file
        if (is_dir($path)) {
            return static::isWritable($path . DIRECTORY_SEPARATOR . uniqid('test_writable', true) . '.tmp');
        }

        // Remember whether the file already existed
        $exists = file_exists($path);

        if (($f = @fopen($path, 'ab')) === false) {
            return false;
        }

        @fclose($f);

        // Delete the file if it didn't exist already
        if (!$exists) {
            static::unlink($path);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public static function getMimeType($file, $magicFile = null, $checkExtension = true)
    {
        $mimeType = parent::getMimeType($file, $magicFile, $checkExtension);

        // Be forgiving of SVG files, etc., that don't have an XML declaration
        if ($checkExtension && ($mimeType === null || !static::canTrustMimeType($mimeType))) {
            return static::getMimeTypeByExtension($file, $magicFile) ?? $mimeType;
        }

        // Handle invalid SVG mime type reported by PHP (https://bugs.php.net/bug.php?id=79045)
        if (strpos($mimeType, 'image/svg') === 0) {
            return 'image/svg+xml';
        }

        return $mimeType;
    }

    /**
     * Returns whether a MIME type can be trusted, or whether we should double-check based on the file extension.
     *
     * @param string $mimeType
     * @return bool
     * @since 3.1.7
     */
    public static function canTrustMimeType(string $mimeType): bool
    {
        return !in_array($mimeType, [
            'application/octet-stream',
            'application/xml',
            'text/html',
            'text/plain',
            'text/xml',
        ], true);
    }

    /**
     * Returns whether the given file path is an SVG image.
     *
     * @param string $file the file name.
     * @param string $magicFile name of the optional magic database file (or alias), usually something like `/path/to/magic.mime`.
     * This will be passed as the second parameter to [finfo_open()](http://php.net/manual/en/function.finfo-open.php)
     * when the `fileinfo` extension is installed. If the MIME type is being determined based via [[getMimeTypeByExtension()]]
     * and this is null, it will use the file specified by [[mimeMagicFile]].
     * @param bool $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return bool
     */
    public static function isSvg(string $file, ?string $magicFile = null, bool $checkExtension = true): bool
    {
        return self::getMimeType($file, $magicFile, $checkExtension) === 'image/svg+xml';
    }

    /**
     * Returns whether the given file path is an GIF image.
     *
     * @param string $file the file name.
     * @param string $magicFile name of the optional magic database file (or alias), usually something like `/path/to/magic.mime`.
     * This will be passed as the second parameter to [finfo_open()](http://php.net/manual/en/function.finfo-open.php)
     * when the `fileinfo` extension is installed. If the MIME type is being determined based via [[getMimeTypeByExtension()]]
     * and this is null, it will use the file specified by [[mimeMagicFile]].
     * @param bool $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return bool
     * @since 3.0.9
     */
    public static function isGif(string $file, ?string $magicFile = null, bool $checkExtension = true): bool
    {
        $mimeType = self::getMimeType($file, $magicFile, $checkExtension);
        return $mimeType === 'image/gif';
    }

    /**
     * Writes contents to a file.
     *
     * @param string $file the file path
     * @param string $contents the new file contents
     * @param array $options options for file write. Valid options are:
     * - `createDirs`: bool, whether to create parent directories if they do
     *   not exist. Defaults to `true`.
     * - `append`: bool, whether the contents should be appended to the
     *   existing contents. Defaults to false.
     * - `lock`: bool, whether a file lock should be used. Defaults to the
     *   `useWriteFileLock` config setting.
     * @throws InvalidArgumentException if the parent directory doesn't exist and `options[createDirs]` is `false`
     * @throws Exception if the parent directory can't be created
     * @throws ErrorException in case of failure
     */
    public static function writeToFile(string $file, string $contents, array $options = [])
    {
        $file = static::normalizePath($file);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            if (!isset($options['createDirs']) || $options['createDirs']) {
                static::createDirectory($dir);
            } else {
                throw new InvalidArgumentException("Cannot write to \"{$file}\" because the parent directory doesn't exist.");
            }
        }

        if (isset($options['lock'])) {
            $lock = (bool)$options['lock'];
        } else {
            $lock = static::useFileLocks();
        }

        if ($lock) {
            $mutex = Craft::$app->getMutex();
            $lockName = md5($file);
            if (!$mutex->acquire($lockName, 2)) {
                throw new ErrorException("Unable to acquire a lock for file \"{$file}\".");
            }
        } else {
            $lockName = $mutex = null;
        }

        $flags = 0;
        if (!empty($options['append'])) {
            $flags |= FILE_APPEND;
        }

        if (file_put_contents($file, $contents, $flags) === false) {
            throw new ErrorException("Unable to write new contents to \"{$file}\".");
        }

        // Invalidate opcache
        static::invalidate($file);

        if ($lock) {
            $mutex->release($lockName);
        }
    }

    /**
     * Creates a `.gitignore` file in the given directory if one doesn’t exist yet.
     *
     * @param string $path
     * @param array $options options for file write. Valid options are:
     * - `createDirs`: bool, whether to create parent directories if they do
     *   not exist. Defaults to `true`.
     * - `lock`: bool, whether a file lock should be used. Defaults to `false`.
     * @throws InvalidArgumentException if the parent directory doesn't exist and `options[createDirs]` is `false`
     * @throws Exception if the parent directory can't be created
     * @throws ErrorException in case of failure
     * @since 3.4.0
     */
    public static function writeGitignoreFile(string $path, array $options = [])
    {
        $gitignorePath = $path . DIRECTORY_SEPARATOR . '.gitignore';

        if (is_file($gitignorePath)) {
            return;
        }

        $contents = "*\n!.gitignore\n";
        $options = array_merge([
            // Prevent a segfault if this is called recursively
            'lock' => false,
        ], $options);

        static::writeToFile($gitignorePath, $contents, $options);
    }

    /**
     * Removes a file or symlink in a cross-platform way
     *
     * @param string $path the file to be deleted
     * @return bool
     * @deprecated in 3.0.0-RC11. Use [[unlink()]] instead.
     */
    public static function removeFile(string $path): bool
    {
        return static::unlink($path);
    }

    /**
     * @inheritdoc
     * @since 3.4.16
     */
    public static function unlink($path)
    {
        // BaseFileHelper::unlink() doesn't seem to catch all possible exceptions
        try {
            return parent::unlink($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Removes all of a directory’s contents recursively.
     *
     * @param string $dir the directory to be deleted recursively.
     * @param array $options options for directory remove. Valid options are:
     * - `traverseSymlinks`: bool, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     * - `filter`: callback (see [[findFiles()]])
     * - `except`: array (see [[findFiles()]])
     * - `only`: array (see [[findFiles()]])
     * @throws InvalidArgumentException if the dir is invalid
     * @throws ErrorException in case of failure
     */
    public static function clearDirectory(string $dir, array $options = [])
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("The dir argument must be a directory: $dir");
        }

        // Adapted from [[removeDirectory()]], plus addition of filters, and minus the root directory removal at the end
        if (!($handle = opendir($dir))) {
            return;
        }

        if (!isset($options['basePath'])) {
            $options['basePath'] = realpath($dir);
            $options = static::normalizeOptions($options);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (static::filterPath($path, $options)) {
                if (is_dir($path)) {
                    static::removeDirectory($path, $options);
                } else {
                    static::unlink($path);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Returns the last modification time for the given path.
     *
     * If the path is a directory, any nested files/directories will be checked as well.
     *
     * @param string $path the directory to be checked
     * @return int Unix timestamp representing the last modification time
     */
    public static function lastModifiedTime($path)
    {
        if (is_file($path)) {
            return filemtime($path);
        }

        $times = [filemtime($path)];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $p => $info) {
            $times[] = filemtime($p);
        }

        return max($times);
    }

    /**
     * Returns whether any files in a source directory have changed, compared to another directory.
     *
     * @param string $dir the source directory to check for changes in
     * @param string $ref the reference directory
     * @return bool
     * @throws InvalidArgumentException if $dir or $ref isn't a directory
     * @throws ErrorException if we can't get a handle on $src
     */
    public static function hasAnythingChanged(string $dir, string $ref): bool
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("The src argument must be a directory: {$dir}");
        }

        if (!is_dir($ref)) {
            throw new InvalidArgumentException("The ref argument must be a directory: {$ref}");
        }

        if (!($handle = opendir($dir))) {
            throw new ErrorException("Unable to open the directory: {$dir}");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $refPath = $ref . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                if (!is_dir($refPath) || static::hasAnythingChanged($path, $refPath)) {
                    return true;
                }
            } else if (!is_file($refPath) || filemtime($path) > filemtime($refPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether file locks can be used when writing to files.
     *
     * @return bool
     */
    public static function useFileLocks(): bool
    {
        if (self::$_useFileLocks !== null) {
            return self::$_useFileLocks;
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (is_bool($generalConfig->useFileLocks)) {
            return self::$_useFileLocks = $generalConfig->useFileLocks;
        }

        // Do we have it cached?
        $cacheService = Craft::$app->getCache();
        if (($cachedVal = $cacheService->get('useFileLocks')) !== false) {
            return self::$_useFileLocks = ($cachedVal === 'y');
        }

        // Try a test lock
        self::$_useFileLocks = false;

        try {
            $mutex = Craft::$app->getMutex();
            $name = uniqid('test_lock', true);
            if (!$mutex->acquire($name)) {
                throw new Exception('Unable to acquire test lock.');
            }
            if (!$mutex->release($name)) {
                throw new Exception('Unable to release test lock.');
            }
            self::$_useFileLocks = true;
        } catch (\Throwable $e) {
            Craft::warning('Write lock test failed: ' . $e->getMessage(), __METHOD__);
        }

        // Cache for two months
        $cachedValue = self::$_useFileLocks ? 'y' : 'n';
        $cacheService->set('useFileLocks', $cachedValue, 5184000);

        return self::$_useFileLocks;
    }

    /**
     * Moves existing files down to `*.1`, `*.2`, etc.
     *
     * @param string $basePath The base path to the first file (sans `.X`)
     * @param int $max The most files that can coexist before we should start deleting them
     * @since 3.0.38
     */
    public static function cycle(string $basePath, int $max = 50)
    {
        // Go through all of them and move them forward.
        for ($i = $max; $i > 0; $i--) {
            $thisFile = $basePath . ($i == 1 ? '' : '.' . ($i - 1));
            if (file_exists($thisFile)) {
                if ($i === $max) {
                    @unlink($thisFile);
                } else {
                    @rename($thisFile, "$basePath.$i");
                }
            }
        }
    }

    /**
     * Invalidates a cached file with `clearstatcache()` and `opcache_invalidate()`.
     *
     * @param string $file the file path
     * @since 3.4.0
     */
    public static function invalidate(string $file)
    {
        clearstatcache(true, $file);
        if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            @opcache_invalidate($file, true);
        }
    }

    /**
     * Zips a file.
     *
     * @param string $path the file/directory path
     * @param string|null $to the target zip file path. If null, the original path will be used, with “.zip” appended to it.
     * @return string the zip file path
     * @throws InvalidArgumentException if `$path` is not a valid file/directory path
     * @throws Exception if the zip cannot be created
     * @since 3.5.0
     */
    public static function zip(string $path, ?string $to = null): string
    {
        $path = static::normalizePath($path);

        if (!file_exists($path)) {
            throw new InvalidArgumentException("No file/directory exists at $path");
        }

        if ($to === null) {
            $to = "$path.zip";
        }

        $zip = new ZipArchive();

        if ($zip->open($to, ZipArchive::CREATE) !== true) {
            throw new Exception("Cannot create zip at $to");
        }

        $name = basename($path);

        if (is_file($path)) {
            $zip->addFile($path, $name);
        } else {
            static::addFilesToZip($zip, $path);
        }

        $zip->close();
        return $to;
    }

    /**
     * Adds all the files in a given directory to a ZipArchive, preserving the nested directory structure.
     *
     * @param ZipArchive $zip the ZipArchive object
     * @param string $dir the directory path
     * @param string|null $prefix the path prefix to use when adding the contents of the directory
     * @param array $options options for file searching. See [[findFiles()]] for available options.
     * @param 3.5.0
     */
    public static function addFilesToZip(ZipArchive $zip, string $dir, ?string $prefix = null, $options = [])
    {
        if (!is_dir($dir)) {
            return;
        }

        if ($prefix !== null) {
            $prefix = static::normalizePath($prefix) . '/';
        } else {
            $prefix = '';
        }

        $files = static::findFiles($dir, $options);

        foreach ($files as $file) {
            // Use forward slashes
            $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
            // Preserve the directory structure within the templates folder
            $zip->addFile($file, $prefix . substr($file, strlen($dir) + 1));
        }
    }

    /**
     * Return a file extension for the given MIME type.
     *
     * @param $mimeType
     * @return string
     * @throws InvalidArgumentException if no known extensions exist for the given MIME type.
     * @since 3.5.15
     */
    public static function getExtensionByMimeType($mimeType): string
    {
        $extensions = FileHelper::getExtensionsByMimeType($mimeType);

        if (empty($extensions)) {
            throw new InvalidArgumentException("No file extensions are known for the MIME Type $mimeType.");
        }

        $extension = reset($extensions);

        // Manually correct for some types.
        switch ($extension) {
            case 'svgz':
                return 'svg';
            case 'jpe':
                return 'jpg';
        }

        return $extension;
    }
}
