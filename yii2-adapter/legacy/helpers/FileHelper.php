<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\errors\MutexException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Throwable;
use UnexpectedValueException;
use yii\base\ErrorException;
use yii\base\Exception;
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
    private static bool $_useFileLocks;

    /**
     * A list of files to be deleted once the request ends.
     *
     * @var array
     */
    private static array $_filesToBeDeleted = [];

    /**
     * @inheritdoc
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR): string
    {
        // Remove any file protocol wrappers
        $path = preg_replace('/^(file:\\/\\/)*/i', '', $path);

        // Is this a UNC network share path?
        $isUnc = (str_starts_with($path, '//') || str_starts_with($path, '\\\\'));

        // Normalize the path
        $path = parent::normalizePath($path, $ds);

        // If it is UNC, add those slashes back in front
        if ($isUnc) {
            $path = $ds . $ds . ltrim($path, $ds);
        }

        return $path;
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
        $to = static::absolutePath($to, ds: $ds);

        if ($from === null) {
            $from = FileHelper::normalizePath(getcwd(), $ds);
        } else {
            $from = static::absolutePath($from, ds: $ds);
        }

        if ($from === $to) {
            return '.';
        }

        if (!str_starts_with($to . $ds, $from . $ds)) {
            return $to;
        }

        return substr($to, strlen($from) + 1);
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
        $to = static::normalizePath($to, $ds);

        // Already absolute?
        if (
            str_starts_with($to, $ds) ||
            preg_match(sprintf('/^[A-Z]:%s/', preg_quote($ds, '/')), $to)
        ) {
            return $to;
        }

        if ($from === null) {
            $from = FileHelper::normalizePath(getcwd(), $ds);
        } else {
            $from = static::absolutePath($from, ds: $ds);
        }

        return static::normalizePath($from . $ds . $to, $ds);
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
        $path = static::absolutePath($path, ds: '/');
        $parentPath = static::absolutePath($parentPath, ds: '/');
        return $path !== $parentPath && Path::isBasePath($parentPath, $path);
    }

    /**
     * @inheritdoc
     */
    public static function copyDirectory($src, $dst, $options = []): void
    {
        if (!isset($options['fileMode'])) {
            $options['fileMode'] = Cms::config()->defaultFileMode;
        }

        if (!isset($options['dirMode'])) {
            $options['dirMode'] = Cms::config()->defaultDirMode;
        }

        $unsupportedOptions = array_diff(array_keys($options), ['fileMode', 'dirMode']);
        if (!empty($unsupportedOptions)) {
            parent::copyDirectory($src, $dst, $options);
            return;
        }

        $src = static::normalizePath($src);
        $dst = static::normalizePath($dst);

        if ($src === $dst || str_starts_with($dst, $src . DIRECTORY_SEPARATOR)) {
            throw new InvalidArgumentException('Trying to copy a directory to itself or a subdirectory.');
        }

        if (!is_dir($src)) {
            throw new InvalidArgumentException("Unable to open directory: $src");
        }

        if (!File::copyDirectory($src, $dst)) {
            throw new ErrorException("Unable to copy directory: $src");
        }

        static::applyModesRecursively(
            $dst,
            (int)$options['dirMode'],
            (int)$options['fileMode'],
        );
    }

    /**
     * @inheritdoc
     */
    public static function createDirectory($path, $mode = null, $recursive = true): bool
    {
        if ($mode === null) {
            $mode = Cms::config()->defaultDirMode;
        }

        if (is_dir($path)) {
            return true;
        }

        if (!File::makeDirectory($path, $mode, $recursive, true) && !is_dir($path)) {
            return false;
        }

        try {
            return @chmod($path, $mode);
        } catch (Throwable $e) {
            throw new Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public static function removeDirectory($dir, $options = []): void
    {
        if (!is_dir($dir)) {
            return;
        }

        if (empty($options) && !is_link($dir) && File::deleteDirectory($dir)) {
            return;
        }

        try {
            parent::removeDirectory($dir, $options);
        } catch (ErrorException $e) {
            if (!is_dir($dir)) {
                return;
            }

            $fs = new Filesystem();

            try {
                $fs->remove($dir);
            } catch (IOException) {
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
            '}',
        ];

        // Replace any control characters in the name with a space.
        $filename = preg_replace("/\\x{00a0}/iu", ' ', $filename);

        // Remove invisible chars from the filename
        // https://github.com/craftcms/cms/issues/12741
        $filename = preg_replace(Str::invisibleCharsPattern(), '', $filename);

        // Strip any characters not allowed.
        $filename = str_replace($disallowedChars, '', strip_tags($filename));

        if (!Craft::$app->getDb()->getSupportsMb4()) {
            // Strip emojis
            $filename = Str::replaceMb4($filename, '');
        }

        // Nuke any trailing or leading .-_
        $filename = trim($filename, '.-_');

        if ($asciiOnly) {
            try {
                // Always use the primary site language, so file paths/names are normalized
                // to ASCII consistently regardless of who is logged in.
                $language = Sites::getPrimarySite()->getLanguage();
            } catch (SiteNotFoundException $e) {
                $language = app()->getLocale();
            }

            $filename = Str::ascii($filename, $language);
        }

        if ($separator !== null) {
            $qSeparator = preg_quote($separator, '/');
            $filename = preg_replace("/[\s$qSeparator]+/u", $separator, $filename);
            $filename = preg_replace("/^$qSeparator+|$qSeparator+$/u", '', $filename);
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

        try {
            return !Finder::create()
                ->ignoreDotFiles(false)
                ->ignoreVCS(false)
                ->files()
                ->in($dir)
                ->hasResults();
        } catch (Throwable $e) {
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
        if (is_dir($file)) {
            return 'directory';
        }

        try {
            $mimeType = parent::getMimeType($file, $magicFile, $checkExtension);
        } catch (ErrorException $e) {
            $mimeType = null;
        }

        if (
            // Be forgiving of SVG files, etc., that don't have an XML declaration
            // also, if we're not supposed to check the extension, but the extension is mp3 and the reported mime type is application/octet-stream,
            // check by extension anyway
            ($checkExtension || (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mp3')) &&
            ($mimeType === null || !static::canTrustMimeType($mimeType))
        ) {
            return static::getMimeTypeByExtension($file, $magicFile) ?? $mimeType;
        }

        // Handle invalid SVG mime type reported by PHP (https://bugs.php.net/bug.php?id=79045)
        if (str_starts_with($mimeType, 'image/svg')) {
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
        return self::getMimeType($file, $magicFile, $checkExtension) === 'image/svg+xml';
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
                Log::info("Could not determine the free disk space for \"$dir\".");
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
            $mutex = Cache::lock(md5($file), 3);
            if (!$mutex->get()) {
                throw new ErrorException("Unable to acquire a lock for file \"$file\".");
            }
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
            $mutex->release();
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
     * @deprecated 6.0.0 use {@see \Illuminate\Support\Facades\File::delete()} instead.
     * @see \Illuminate\Support\Facades\File::delete()
     * @since 3.4.16
     */
    public static function unlink($path): bool
    {
        return File::delete($path);
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
    public static function clearDirectory(string $dir, array $options = []): void
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("The dir argument must be a directory: $dir");
        }

        if (empty($options)) {
            File::cleanDirectory($dir);
            return;
        }

        if (!isset($options['basePath'])) {
            $options['basePath'] = realpath($dir);
            $options = static::normalizeOptions($options);
        }

        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->depth('== 0')
            ->in($dir);

        foreach ($finder as $item) {
            $path = $item->getPathname();

            if (static::filterPath($path, $options)) {
                if ($item->isDir() && !$item->isLink()) {
                    try {
                        static::removeDirectory($path, $options);
                    } catch (UnexpectedValueException $e) {
                        // Ignore if the folder has already been removed.
                        if (!str_contains($e->getMessage(), 'No such file or directory')) {
                            Log::info("Tried to remove " . $path . ", but it doesn't exist.");
                            throw $e;
                        }
                    }
                } else {
                    static::unlink($path);
                }
            }
        }
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

        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($path);

        foreach ($finder as $item) {
            $times[] = $item->getMTime();
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

        try {
            $finder = Finder::create()
                ->ignoreDotFiles(false)
                ->ignoreVCS(false)
                ->in($dir);
        } catch (Throwable) {
            throw new ErrorException("Unable to open the directory: $dir");
        }

        try {
            foreach ($finder as $item) {
                $refPath = $ref . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item->getRelativePathname());

                if ($item->isDir()) {
                    if (!is_dir($refPath)) {
                        return true;
                    }
                    continue;
                }

                if (!is_file($refPath) || $item->getMTime() > filemtime($refPath)) {
                    return true;
                }
            }
        } catch (Throwable) {
            throw new ErrorException("Unable to open the directory: $dir");
        }

        return false;
    }

    /**
     * Applies directory and file modes recursively.
     *
     * @param string $path
     * @param int $dirMode
     * @param int $fileMode
     */
    private static function applyModesRecursively(string $path, int $dirMode, int $fileMode): void
    {
        @chmod($path, $dirMode);

        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($path);

        foreach ($finder as $item) {
            @chmod($item->getPathname(), $item->isDir() ? $dirMode : $fileMode);
        }
    }

    /**
     * Returns whether file locks can be used when writing to files.
     *
     * @return bool
     */
    public static function useFileLocks(): bool
    {
        if (isset(self::$_useFileLocks)) {
            return self::$_useFileLocks;
        }

        $generalConfig = Cms::config();
        if (is_bool($generalConfig->useFileLocks)) {
            return self::$_useFileLocks = $generalConfig->useFileLocks;
        }

        // Do we have it cached?
        if (($cachedVal = Cache::get('useFileLocks')) !== false) {
            return self::$_useFileLocks = ($cachedVal === 'y');
        }

        // Try a test lock
        self::$_useFileLocks = false;

        try {
            $name = uniqid('test_lock', true);
            $mutex = Cache::lock($name);
            if (!$mutex->get()) {
                throw new MutexException($name, 'Unable to acquire test lock.');
            }
            if (!$mutex->release()) {
                throw new MutexException($name, 'Unable to release test lock.');
            }
            self::$_useFileLocks = true;
        } catch (Throwable $e) {
            Log::warning('Write lock test failed: ' . $e->getMessage(), [__METHOD__]);
        }

        // Cache for two months
        $cachedValue = self::$_useFileLocks ? 'y' : 'n';
        Cache::put('useFileLocks', $cachedValue, now()->addMonths(2));

        return self::$_useFileLocks;
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
    public static function invalidate(string $file): void
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
     * @since 3.5.0
     */
    public static function addFilesToZip(ZipArchive $zip, string $dir, ?string $prefix = null, array $options = []): void
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
     * @param string $mimeType
     * @param bool $preferShort
     * @param string|null $magicFile
     * @return string
     * @throws InvalidArgumentException if no known extensions exist for the given MIME type.
     * @since 3.5.15
     */
    public static function getExtensionByMimeType($mimeType, $preferShort = false, $magicFile = null): string
    {
        // cover the ambiguous, web-friendly MIME types up front
        switch (strtolower($mimeType)) {
            case 'application/msword': return 'doc';
            case 'application/x-yaml': return 'yml';
            case 'application/xml': return 'xml';
            case 'audio/mp4': return 'm4a';
            case 'audio/mpeg': return 'mp3';
            case 'audio/ogg': return 'ogg';
            case 'image/heic': return 'heic';
            case 'image/jpeg': return 'jpg';
            case 'image/svg+xml': return 'svg';
            case 'image/tiff': return 'tif';
            case 'text/calendar': return 'ics';
            case 'text/html': return 'html';
            case 'text/markdown': return 'md';
            case 'text/plain': return 'txt';
            case 'video/mp4': return 'mp4';
            case 'video/mpeg': return 'mpg';
            case 'video/quicktime': return 'mov';
        }

        $extensions = self::getExtensionsByMimeType($mimeType);

        if (empty($extensions)) {
            throw new InvalidArgumentException("No file extensions are known for the MIME Type $mimeType.");
        }

        return reset($extensions);
    }

    /**
     * Deletes a file after the request ends.
     *
     * @param string $filename
     * @since 4.0.0
     */
    public static function deleteFileAfterRequest(string $filename): void
    {
        if (empty(self::$_filesToBeDeleted)) {
            register_shutdown_function([static::class, 'deleteQueuedFiles']);
        }

        self::$_filesToBeDeleted[] = $filename;
    }

    /**
     * Delete all files queued up for deletion.
     *
     * @since 4.0.0
     */
    public static function deleteQueuedFiles(): void
    {
        foreach (array_unique(self::$_filesToBeDeleted) as $source) {
            if (file_exists($source)) {
                self::unlink($source);
            }
        }

        self::$_filesToBeDeleted = [];
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
        $name = pathinfo($baseName, PATHINFO_FILENAME);
        $ext = pathinfo($baseName, PATHINFO_EXTENSION);
        if ($ext !== '') {
            $ext = ".$ext";
        }
        $extLength = strlen($ext);
        $maxLength = 232; // 255 - 23 (entropy chars)
        if (strlen($name) + $extLength > $maxLength) {
            $name = substr($name, 0, $maxLength - $extLength);
        }
        return uniqid($name, true) . $ext;
    }
}
