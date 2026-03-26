<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use CraftCms\Cms\Support\File;
use FilesystemIterator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use UnexpectedValueException;
use yii\base\ErrorException;
use yii\base\Exception;
use ZipArchive;

/**
 * Class FileHelper
 *
 * Backwards-compatible wrapper around {@see File}.
 * All logic lives in `CraftCms\Cms\Support\File`; this class
 * delegates to it and re-throws Yii2 exception types for BC.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see File} instead.
 */
class FileHelper extends \yii\helpers\FileHelper
{
    /**
     * @inheritdoc
     */
    public static $mimeMagicFile = '@app/config/mimeTypes.php';

    /**
     * @inheritdoc
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR): string
    {
        return File::normalizePath((string) $path, (string) $ds);
    }

    /**
     * Returns a relative path based on a source location or the current working directory.
     *
     * @param string $to The target path.
     * @param string|null $from The source location. Defaults to the current working directory.
     * @param string $ds the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
     * @return string The relative path if possible, or an absolute path if the directory is not contained within `$from`.
     * @since 4.3.5
     */
    public static function relativePath(
        string $to,
        ?string $from = null,
        string $ds = DIRECTORY_SEPARATOR,
    ): string {
        return File::relativePath($to, $from, $ds);
    }

    /**
     * Returns an absolute path based on a source location or the current working directory.
     *
     * @param string $to The target path.
     * @param string|null $from The source location. Defaults to the current working directory.
     * @param string $ds the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
     * @return string
     * @since 4.3.5
     */
    public static function absolutePath(
        string $to,
        ?string $from = null,
        string $ds = DIRECTORY_SEPARATOR,
    ): string {
        return File::absolutePath($to, $from, $ds);
    }

    /**
     * Returns whether the given path is within another path.
     *
     * @param string $path the path to check
     * @param string $parentPath the parent path that `$path` should be within
     * @return bool
     */
    public static function isWithin(string $path, string $parentPath): bool
    {
        $path = File::absolutePath($path, ds: '/');
        $parentPath = File::absolutePath($parentPath, ds: '/');

        return $path !== $parentPath && Path::isBasePath($parentPath, $path);
    }

    /**
     * @inheritdoc
     */
    public static function copyDirectory($src, $dst, $options = []): void
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
    public static function createDirectory($path, $mode = null, $recursive = true): bool
    {
        try {
            return File::makeDirectory((string) $path, $mode !== null ? (int) $mode : null, (bool) $recursive);
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public static function removeDirectory($dir, $options = []): void
    {
        try {
            parent::removeDirectory($dir, $options);
        } catch (ErrorException $e) {
            // Try Symfony's thing as a fallback
            $fs = new SymfonyFilesystem();

            try {
                $fs->remove($dir);
            } catch (IOException) {
                // throw the original exception instead
                throw $e;
            }
        }
    }

    /**
     * Removes all of a directory's contents recursively.
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
    public static function clearDirectory(string $dir, array $options = []): void
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
                    try {
                        static::removeDirectory($path, $options);
                    } catch (UnexpectedValueException $e) {
                        // Ignore if the folder has already been removed.
                        if (!str_contains($e->getMessage(), 'No such file or directory')) {
                            Log::warning("Tried to remove " . $path . ", but it doesn't exist.");
                            throw $e;
                        }
                    }
                } else {
                    static::unlink($path);
                }
            }
        }
        closedir($handle);
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
        return File::sanitizeFilename($filename, $options);
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

        try {
            return !Finder::create()
                ->ignoreDotFiles(false)
                ->ignoreVCS(false)
                ->files()
                ->in($dir)
                ->hasResults();
        } catch (Throwable) {
            throw new ErrorException("Unable to open the directory: $dir");
        }
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
    public static function getMimeType($file, $magicFile = null, $checkExtension = true): ?string
    {
        return File::getMimeType((string) $file, (bool) $checkExtension);
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
        return File::canTrustMimeType($mimeType);
    }

    /**
     * @inheritdoc
     */
    public static function getMimeTypeByExtension($file, $magicFile = null): ?string
    {
        return File::getMimeTypeByExtension((string) $file);
    }

    /**
     * @inheritdoc
     */
    public static function getExtensionsByMimeType($mimeType, $magicFile = null): array
    {
        return MimeTypes::getDefault()->getExtensions(strtolower((string) $mimeType));
    }

    /**
     * Returns whether the given file path is an SVG image.
     *
     * @param string $file the file name.
     * @param string|null $magicFile name of the optional magic database file (or alias), usually something like `/path/to/magic.mime`.
     * This will be passed as the second parameter to [finfo_open()](https://php.net/manual/en/function.finfo-open.php)
     * when the `fileinfo` extension is installed. If the MIME type is being determined based via [[getMimeTypeByExtension()]]
     * and this is null, it will use the file specified by [[mimeMagicFile]].
     * @param bool $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return bool
     */
    public static function isSvg(string $file, ?string $magicFile = null, bool $checkExtension = true): bool
    {
        return File::isSvg($file, $checkExtension);
    }

    /**
     * Returns whether the given file path is an GIF image.
     *
     * @param string $file the file name.
     * @param string|null $magicFile name of the optional magic database file (or alias), usually something like `/path/to/magic.mime`.
     * This will be passed as the second parameter to [finfo_open()](https://php.net/manual/en/function.finfo-open.php)
     * when the `fileinfo` extension is installed. If the MIME type is being determined based via [[getMimeTypeByExtension()]]
     * and this is null, it will use the file specified by [[mimeMagicFile]].
     * @param bool $checkExtension whether to use the file extension to determine the MIME type in case
     * `finfo_open()` cannot determine it.
     * @return bool
     * @since 3.0.9
     */
    public static function isGif(string $file, ?string $magicFile = null, bool $checkExtension = true): bool
    {
        return File::isGif($file, $checkExtension);
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
     * @throws InvalidArgumentException if the parent directory doesn't exist and `options[createDirs]` is `false`
     * @throws Exception if the parent directory can't be created
     * @throws ErrorException in case of failure
     */
    public static function writeToFile(string $file, string $contents, array $options = []): void
    {
        $file = static::normalizePath($file);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            if (!isset($options['createDirs']) || $options['createDirs']) {
                static::createDirectory($dir);
            } else {
                throw new InvalidArgumentException("Cannot write to \"$file\" because the parent directory doesn't exist.");
            }
        }

        if (!static::isWritable($file)) {
            throw new ErrorException("The file path \"$file\" is not writable.");
        }

        if (function_exists('disk_free_space')) {
            $freeBytes = disk_free_space($dir);

            if ($freeBytes === false) {
                Log::warning("Could not determine the free disk space for \"$dir\".");
            } else {
                $bytes = StringHelper::byteLength($contents);
                if ($bytes > $freeBytes) {
                    throw new ErrorException(sprintf(
                        "Insufficient disk space to write \"%s\". %s bytes free, %s bytes required.",
                        $file,
                        $freeBytes,
                        $bytes,
                    ));
                }
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
            if (!$mutex->acquire($lockName, 3)) {
                throw new ErrorException("Unable to acquire a lock for file \"$file\".");
            }
        } else {
            $lockName = $mutex = null;
        }

        $flags = 0;
        if (!empty($options['append'])) {
            $flags |= FILE_APPEND;
        }

        if (file_put_contents($file, $contents, $flags) === false) {
            throw new ErrorException("Unable to write new contents to \"$file\".");
        }

        // Invalidate opcache
        static::invalidate($file);

        if ($lock) {
            $mutex->release($lockName);
        }
    }

    /**
     * Creates a `.gitignore` file in the given directory if one doesn't exist yet.
     *
     * @param string $path
     * @param array $options options for file write. Valid options are:
     * - `createDirs`: bool, whether to create parent directories if they do
     *   not exist. Defaults to `true`.
     * @throws InvalidArgumentException if the parent directory doesn't exist and `options[createDirs]` is `false`
     * @throws Exception if the parent directory can't be created
     * @throws ErrorException in case of failure
     * @since 3.4.0
     */
    public static function writeGitignoreFile(string $path, array $options = []): void
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
     * @inheritdoc
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\File::delete()} instead.
     * @see \CraftCms\Cms\Support\File::delete()
     * @since 3.4.16
     */
    public static function unlink($path): bool
    {
        return File::delete((string) $path);
    }

    /**
     * Traverses up the filesystem looking for the closest file to the given directory.
     *
     * @param string $dir the directory at or above which the file will be looked for
     * @param array $options options for file searching. See [[findFiles()]].
     * @return string|null the closest matching file
     * @throws InvalidArgumentException if the directory is invalid
     * @since 4.3.5
     */
    public static function findClosestFile(string $dir, array $options = []): ?string
    {
        $options['recursive'] = false;
        $dir = static::absolutePath($dir, ds: '/');
        while (true) {
            $exists = file_exists($dir);
            try {
                $files = static::findFiles($dir, $options);
            } catch (InvalidArgumentException|\yii\base\InvalidArgumentException $e) {
                if ($exists) {
                    return null;
                }
                throw $e;
            }

            if (!empty($files)) {
                return reset($files);
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                return null;
            }
            $dir = $parent;
        }
    }

    /**
     * Returns the last modification time for the given path.
     *
     * If the path is a directory, any nested files/directories will be checked as well.
     *
     * @param string $path the directory to be checked
     * @return int Unix timestamp representing the last modification time
     */
    public static function lastModifiedTime(string $path): int
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
            throw new InvalidArgumentException("The src argument must be a directory: $dir");
        }

        if (!is_dir($ref)) {
            throw new InvalidArgumentException("The ref argument must be a directory: $ref");
        }

        if (!($handle = opendir($dir))) {
            throw new ErrorException("Unable to open the directory: $dir");
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
            } elseif (!is_file($refPath) || filemtime($path) > filemtime($refPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether file locks can be used when writing to files.
     *
     * @return bool
     * @deprecated 6.0.0 File locking has been removed.
     */
    public static function useFileLocks(): bool
    {
        return false;
    }

    /**
     * Moves existing files down to `*.1`, `*.2`, etc.
     *
     * @param string $basePath The base path to the first file (sans `.X`)
     * @param int $max The most files that can coexist before we should start deleting them
     * @since 3.0.38
     */
    public static function cycle(string $basePath, int $max = 50): void
    {
        File::cycle($basePath, $max);
    }

    /**
     * Invalidates a cached file with `clearstatcache()` and `opcache_invalidate()`.
     *
     * @param string $file the file path
     * @since 3.4.0
     */
    public static function invalidate(string $file): void
    {
        File::invalidate($file);
    }

    /**
     * Zips a file.
     *
     * @param string $path the file/directory path
     * @param string|null $to the target zip file path. If null, the original path will be used, with ".zip" appended to it.
     * @return string the zip file path
     * @throws InvalidArgumentException if `$path` is not a valid file/directory path
     * @throws Exception if the zip cannot be created
     * @since 3.5.0
     */
    public static function zip(string $path, ?string $to = null): string
    {
        try {
            return File::zip($path, $to);
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Adds all the files in a given directory to a ZipArchive, preserving the nested directory structure.
     *
     * @param ZipArchive $zip the ZipArchive object
     * @param string $dir the directory path
     * @param string|null $prefix the path prefix to use when adding the contents of the directory
     * @param array $options options for file searching. See [[findFiles()]] for available options.
     * @since 3.5.0
     */
    public static function addFilesToZip(ZipArchive $zip, string $dir, ?string $prefix = null, array $options = []): void
    {
        File::addFilesToZip($zip, $dir, $prefix,
            only: $options['only'] ?? [],
            except: $options['except'] ?? [],
            recursive: $options['recursive'] ?? true,
        );
    }

    /**
     * Return a file extension for the given MIME type.
     *
     * @param string $mimeType
     * @param bool $preferShort
     * @param string|null $magicFile
     * @return string
     * @throws InvalidArgumentException if no known extensions exist for the given MIME type.
     * @since 3.5.15
     */
    public static function getExtensionByMimeType($mimeType, $preferShort = false, $magicFile = null): string
    {
        return File::getExtensionByMimeType((string) $mimeType);
    }

    /**
     * Deletes a file after the request ends.
     *
     * @param string $filename
     * @since 4.0.0
     */
    public static function deleteFileAfterRequest(string $filename): void
    {
        app()->terminating(function() use ($filename) {
            File::delete($filename);
        });
    }

    /**
     * Delete all files queued up for deletion.
     *
     * @since 4.0.0
     * @deprecated No longer queues files for batch deletion. Files are now deleted individually via terminating callbacks.
     */
    public static function deleteQueuedFiles(): void
    {
    }

    /**
     * Returns a unique version of a filename with `uniqid()`, ensuring the result is at most 255 characters.
     *
     * @param string $baseName The original filename, or just a file extension prefixed with a `.`.
     * @return string
     * @since 4.4.3
     */
    public static function uniqueName(string $baseName)
    {
        return File::uniqueName($baseName);
    }
}
