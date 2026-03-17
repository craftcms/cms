<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use CraftCms\Cms\Support\File;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Mime\MimeTypes;
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
        return File::isWithin($path, $parentPath);
    }

    /**
     * @inheritdoc
     */
    public static function copyDirectory($src, $dst, $options = []): void
    {
        try {
            File::copyDirectory((string) $src, (string) $dst, $options);
        } catch (InvalidArgumentException $e) {
            // Unsupported options — fall back to parent Yii implementation
            if (str_starts_with($e->getMessage(), 'Unsupported copyDirectory options')) {
                parent::copyDirectory($src, $dst, $options);
                return;
            }
            throw $e;
        } catch (\ErrorException $e) {
            throw new ErrorException($e->getMessage(), (int) $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public static function createDirectory($path, $mode = null, $recursive = true): bool
    {
        try {
            return File::createDirectory((string) $path, $mode !== null ? (int) $mode : null, (bool) $recursive);
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public static function removeDirectory($dir, $options = []): void
    {
        File::removeDirectory((string) $dir, $options);
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
        try {
            File::clearDirectory($dir, $options);
        } catch (\ErrorException $e) {
            throw new ErrorException($e->getMessage(), (int) $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e);
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
        // Determine stripEmoji from the Yii DB connection if not explicitly set
        if (!array_key_exists('stripEmoji', $options)) {
            try {
                $options['stripEmoji'] = !Craft::$app->getDb()->getSupportsMb4();
            } catch (\Throwable) {
                $options['stripEmoji'] = true;
            }
        }

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
        try {
            return File::isDirectoryEmpty($dir);
        } catch (\ErrorException $e) {
            throw new ErrorException($e->getMessage(), (int) $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e);
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
        return File::isWritable($path);
    }

    /**
     * @inheritdoc
     */
    public static function getMimeType($file, $magicFile = null, $checkExtension = true): ?string
    {
        return File::getMimeType((string) $file, $magicFile, (bool) $checkExtension);
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
        return File::getMimeTypeByExtension((string) $file, $magicFile);
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
        return File::isSvg($file, $magicFile, $checkExtension);
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
        return File::isGif($file, $magicFile, $checkExtension);
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
    public static function writeToFile(string $file, string $contents, array $options = []): void
    {
        try {
            File::writeToFile($file, $contents, $options);
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        } catch (\ErrorException $e) {
            throw new ErrorException($e->getMessage(), (int) $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e);
        }
    }

    /**
     * Creates a `.gitignore` file in the given directory if one doesn't exist yet.
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
    public static function writeGitignoreFile(string $path, array $options = []): void
    {
        try {
            File::writeGitignoreFile($path, $options);
        } catch (RuntimeException $e) {
            throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
        } catch (\ErrorException $e) {
            throw new ErrorException($e->getMessage(), (int) $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e);
        }
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
        try {
            return File::findClosestFile($dir, $options);
        } catch (InvalidArgumentException $e) {
            throw new \yii\base\InvalidArgumentException($e->getMessage(), (int) $e->getCode(), $e);
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
        return File::lastModifiedTime($path);
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
        try {
            return File::hasAnythingChanged($dir, $ref);
        } catch (\ErrorException $e) {
            throw new ErrorException($e->getMessage(), (int) $e->getCode(), $e->getSeverity(), $e->getFile(), $e->getLine(), $e);
        }
    }

    /**
     * Returns whether file locks can be used when writing to files.
     *
     * @return bool
     */
    public static function useFileLocks(): bool
    {
        return File::useFileLocks();
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
        File::addFilesToZip($zip, $dir, $prefix, $options);
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
        return File::getExtensionByMimeType((string) $mimeType, (bool) $preferShort, $magicFile);
    }

    /**
     * Finds files in the given directory.
     *
     * @param string $dir the directory to search in
     * @param array $options options for file searching. Valid options are:
     * - `only`: array, list of glob patterns for file inclusion (e.g. `['*.php', '*.js']`).
     * - `except`: array, list of glob patterns for file exclusion (e.g. `['*.log']`).
     * - `recursive`: bool, whether to search recursively. Defaults to `true`.
     * - `caseSensitive`: bool, whether pattern matching should be case-sensitive. Defaults to `true`.
     * - `filter`: callable, a callback receiving the file path, returning `true` to include.
     * @return array list of found file paths
     * @throws InvalidArgumentException if the directory is invalid
     */
    public static function findFiles($dir, $options = []): array
    {
        return File::findFiles((string) $dir, $options);
    }

    /**
     * Deletes a file after the request ends.
     *
     * @param string $filename
     * @since 4.0.0
     */
    public static function deleteFileAfterRequest(string $filename): void
    {
        File::deleteFileAfterRequest($filename);
    }

    /**
     * Delete all files queued up for deletion.
     *
     * @since 4.0.0
     */
    public static function deleteQueuedFiles(): void
    {
        File::deleteQueuedFiles();
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
