<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\Sites;
use ErrorException;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Mime\MimeTypes;
use Throwable;
use ZipArchive;

use function Illuminate\Filesystem\join_paths;

class File extends \Illuminate\Support\Facades\File
{
    /**
     * Normalizes a file/directory path.
     *
     * Normalizes directory separators, resolves `.` and `..` segments,
     * strips `file://` protocol wrappers, and preserves UNC network paths.
     *
     * @param  string  $path  the file/directory path to be normalized
     * @param  string  $ds  the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
     * @return string the normalized file/directory path
     */
    public static function normalizePath(string $path, string $ds = DIRECTORY_SEPARATOR): string
    {
        // Remove any file protocol wrappers
        $path = preg_replace('/^(file:\\/\\/)*/i', '', $path);

        // Is this a UNC network share path?
        $isUnc = (str_starts_with((string) $path, '//') || str_starts_with((string) $path, '\\\\'));

        // Normalize separators and trim trailing
        $path = rtrim(strtr($path, '/\\', $ds.$ds), $ds);

        // Fast path: if there are no `.` segments or double separators, skip resolution
        if (! str_contains($ds.$path, $ds.'.') && ! str_contains($path, $ds.$ds)) {
            if ($isUnc) {
                return $ds.$ds.ltrim($path, $ds);
            }

            return $path;
        }

        // Skip stream wrappers (phar://, etc.)
        foreach (stream_get_wrappers() as $protocol) {
            if (str_starts_with($path, "$protocol://")) {
                return $path;
            }
        }

        // Resolve `.` and `..` segments and collapse double separators
        $parts = [];

        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && ! empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || ($part === '' && ! empty($parts))) {
                continue;
            } else {
                $parts[] = $part;
            }
        }

        $path = implode($ds, $parts);

        // Restore UNC prefix if needed
        if ($isUnc) {
            $path = $ds.$ds.ltrim($path, $ds);
        }

        return $path === '' ? '.' : $path;
    }

    /**
     * Returns a relative path based on a source location or the current working directory.
     *
     * @param  string  $to  The target path.
     * @param  string|null  $from  The source location. Defaults to the current working directory.
     * @param  string  $ds  the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
     * @return string The relative path if possible, or an absolute path if the directory is not contained within `$from`.
     */
    public static function relativePath(
        string $to,
        ?string $from = null,
        string $ds = DIRECTORY_SEPARATOR,
    ): string {
        $to = static::absolutePath($to, ds: $ds);

        if ($from === null) {
            $from = static::normalizePath(getcwd(), $ds);
        } else {
            $from = static::absolutePath($from, ds: $ds);
        }

        if ($from === $to) {
            return '.';
        }

        if (! str_starts_with($to.$ds, $from.$ds)) {
            return $to;
        }

        return substr($to, strlen($from) + 1);
    }

    /**
     * Returns an absolute path based on a source location or the current working directory.
     *
     * @param  string  $to  The target path.
     * @param  string|null  $from  The source location. Defaults to the current working directory.
     * @param  string  $ds  the directory separator to be used in the normalized result. Defaults to `DIRECTORY_SEPARATOR`.
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
            $from = static::normalizePath(getcwd(), $ds);
        } else {
            $from = static::absolutePath($from, ds: $ds);
        }

        return static::normalizePath($from.$ds.$to, $ds);
    }

    /**
     * Creates a new directory.
     *
     * @param  string  $path  path of the directory to be created.
     * @param  int|null  $mode  the permission to be set for the created directory. Defaults to `Cms::config()->defaultDirMode`.
     * @param  bool  $recursive  whether to create parent directories if they do not exist.
     * @return bool whether the directory is created successfully
     *
     * @throws RuntimeException if the directory cannot be created
     */
    public static function makeDirectory(string $path, ?int $mode = null, bool $recursive = true, $force = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return app(Filesystem::class)->makeDirectory($path, $mode ?? Cms::config()->defaultDirMode, $recursive, $force);
    }

    /**
     * Removes all of a directory's contents recursively.
     *
     * @param  string  $directory  the directory to be cleaned.
     * @param  array  $except  list of glob patterns for files/directories to exclude. Patterns ending with `/` match directories only.
     */
    public static function cleanDirectory(string $directory, array $except = []): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        if (empty($except)) {
            return app(Filesystem::class)->cleanDirectory($directory);
        }

        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->depth('== 0')
            ->in($directory);

        // Separate directory-specific patterns (ending with '/') from file patterns
        $fileExcept = [];
        $dirExcept = [];
        foreach ($except as $pattern) {
            if (str_ends_with((string) $pattern, '/')) {
                $dirExcept[] = rtrim((string) $pattern, '/');
            } else {
                $fileExcept[] = $pattern;
            }
        }

        foreach ($finder as $item) {
            $name = $item->getFilename();

            // Check 'except' patterns
            $excluded = false;
            $patternsToCheck = $item->isDir() ? array_merge($fileExcept, $dirExcept) : $fileExcept;
            foreach ($patternsToCheck as $pattern) {
                if (fnmatch($pattern, $name)) {
                    $excluded = true;
                    break;
                }
            }

            if ($excluded) {
                continue;
            }

            if ($item->isDir() && ! $item->isLink()) {
                app(Filesystem::class)->deleteDirectory($item->getPathname());

                continue;
            }

            app(Filesystem::class)->delete($item->getPathname());
        }

        return true;
    }

    /**
     * Sanitizes a filename.
     *
     * @param  string  $filename  the filename to sanitize
     * @param  array  $options  options for sanitization. Valid options are:
     *                          - `asciiOnly`: bool, whether only ASCII characters should be allowed. Defaults to false.
     *                          - `separator`: string|null, the separator character to use in place of whitespace. Defaults to '-'. If set to null, whitespace will be preserved.
     *                          - `stripEmoji`: bool|null, whether to strip emoji characters. Defaults to null (auto-detect based on DB charset support).
     *                          When null, emojis are always stripped as a safe default. Pass `false` to preserve them.
     * @return string The cleansed filename
     */
    public static function sanitizeFilename(string $filename, array $options = []): string
    {
        if (! array_key_exists('stripEmoji', $options)) {
            try {
                $options['stripEmoji'] = ! Craft::$app->getDb()->getSupportsMb4();
            } catch (Throwable) {
                $options['stripEmoji'] = true;
            }
        }

        $asciiOnly = $options['asciiOnly'] ?? false;
        $separator = array_key_exists('separator', $options) ? $options['separator'] : '-';
        $stripEmoji = $options['stripEmoji'] ?? true;
        $disallowedChars = [
            "\xe2\x80\x93",
            "\xe2\x80\x94",
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
        $filename = preg_replace('/\\x{00a0}/iu', ' ', $filename);

        // Remove invisible chars from the filename
        // https://github.com/craftcms/cms/issues/12741
        $filename = preg_replace(Str::invisibleCharsPattern(), '', (string) $filename);

        // Strip any characters not allowed.
        $filename = str_replace($disallowedChars, '', strip_tags((string) $filename));

        if ($stripEmoji) {
            $filename = Str::replaceMb4($filename, '');
        }

        // Nuke any trailing or leading .-_
        $filename = trim($filename, '.-_');

        if ($asciiOnly) {
            try {
                // Always use the primary site language, so file paths/names are normalized
                // to ASCII consistently regardless of who is logged in.
                $language = Sites::getPrimarySite()->getLanguage();
            } catch (SiteNotFoundException) {
                $language = app()->getLocale();
            }

            $filename = Str::ascii($filename, $language);
        }

        if ($separator !== null) {
            $qSeparator = preg_quote($separator, '/');
            $filename = preg_replace("/[\s$qSeparator]+/u", $separator, $filename);
            $filename = preg_replace("/^$qSeparator+|$qSeparator+$/u", '', (string) $filename);
        }

        return $filename;
    }

    /**
     * Tests whether a file/directory is writable.
     *
     * @param  string  $path  the file/directory path to test
     * @return bool whether the path is writable
     */
    public static function isWritable(string $path): bool
    {
        // If it's a directory, test on a temp sub file
        if (is_dir($path)) {
            return static::isWritable($path.DIRECTORY_SEPARATOR.uniqid('test_writable', true).'.tmp');
        }

        // Remember whether the file already existed
        $exists = file_exists($path);

        if (($f = @fopen($path, 'ab')) === false) {
            return false;
        }

        @fclose($f);

        // Delete the file if it didn't exist already
        if (! $exists) {
            app(Filesystem::class)->delete($path);
        }

        return true;
    }

    /**
     * Returns the MIME type of the specified file.
     *
     * @param  string  $file  the file name.
     * @param  bool  $checkExtension  whether to use the file extension to determine the MIME type in case
     *                                `finfo_open()` cannot determine it.
     * @return string|null the MIME type
     */
    public static function getMimeType(string $file, bool $checkExtension = true): ?string
    {
        if (is_dir($file)) {
            return 'directory';
        }

        $mimeType = null;

        try {
            if (is_file($file)) {
                $mimeType = MimeTypes::getDefault()->guessMimeType($file);
            }
        } catch (Throwable) {
            // Ignore errors from finfo/mime detection
        }

        if (
            // Be forgiving of SVG files, etc., that don't have an XML declaration
            // also, if we're not supposed to check the extension, but the extension is mp3 and the reported mime type is application/octet-stream,
            // check by extension anyway
            ($checkExtension || (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'mp3')) &&
            ($mimeType === null || ! static::canTrustMimeType($mimeType))
        ) {
            return static::getMimeTypeByExtension($file) ?? $mimeType;
        }

        // Handle invalid SVG mime type reported by PHP (https://bugs.php.net/bug.php?id=79045)
        if ($mimeType !== null && str_starts_with($mimeType, 'image/svg')) {
            return 'image/svg+xml';
        }

        return $mimeType;
    }

    /**
     * Returns whether a MIME type can be trusted, or whether we should double-check based on the file extension.
     */
    public static function canTrustMimeType(string $mimeType): bool
    {
        return ! in_array($mimeType, [
            'application/octet-stream',
            'application/xml',
            'text/html',
            'text/plain',
            'text/xml',
        ], true);
    }

    /**
     * Returns the MIME type based on the file extension.
     *
     * @param  string  $file  the file name or path.
     * @return string|null the MIME type, or null if the extension is not known.
     */
    public static function getMimeTypeByExtension(string $file): ?string
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($ext === '') {
            return null;
        }

        $mimeTypes = MimeTypes::getDefault()->getMimeTypes(strtolower($ext));

        return $mimeTypes[0] ?? null;
    }

    /**
     * Returns whether the given file path is an SVG image.
     *
     * @param  string  $file  the file name.
     * @param  bool  $checkExtension  whether to use the file extension to determine the MIME type in case
     *                                `finfo_open()` cannot determine it.
     */
    public static function isSvg(string $file, bool $checkExtension = true): bool
    {
        return static::getMimeType($file, $checkExtension) === 'image/svg+xml';
    }

    /**
     * Returns whether the given file path is a GIF image.
     *
     * @param  string  $file  the file name.
     * @param  bool  $checkExtension  whether to use the file extension to determine the MIME type in case
     *                                `finfo_open()` cannot determine it.
     */
    public static function isGif(string $file, bool $checkExtension = true): bool
    {
        return static::getMimeType($file, $checkExtension) === 'image/gif';
    }

    /**
     * Writes contents to a file.
     *
     * @param  string  $file  the file path
     * @param  string  $contents  the new file contents
     * @param  bool  $append  whether to append to existing contents
     *
     * @throws ErrorException in case of failure
     */
    public static function writeToFile(string $file, string $contents, bool $append = false): void
    {
        $file = static::normalizePath($file);

        static::makeDirectory(dirname($file));

        $flags = $append ? FILE_APPEND : 0;

        if (file_put_contents($file, $contents, $flags) === false) {
            throw new ErrorException("Unable to write new contents to \"$file\".");
        }

        // Invalidate opcache
        static::invalidate($file);
    }

    /**
     * Creates a `.gitignore` file in the given directory if one doesn't exist yet.
     */
    public static function writeGitignoreFile(string $path): void
    {
        $gitignorePath = join_paths($path, '.gitignore');

        if (is_file($gitignorePath)) {
            return;
        }

        app(Filesystem::class)->put($gitignorePath, "*\n!.gitignore\n");
    }

    /**
     * Moves existing files down to `*.1`, `*.2`, etc.
     *
     * @param  string  $basePath  The base path to the first file (sans `.X`)
     * @param  int  $max  The most files that can coexist before we should start deleting them
     */
    public static function cycle(string $basePath, int $max = 50): void
    {
        // Go through all of them and move them forward.
        for ($i = $max; $i > 0; $i--) {
            $thisFile = $basePath.($i === 1 ? '' : '.'.($i - 1));

            if (! file_exists($thisFile)) {
                continue;
            }

            if ($i === $max) {
                @unlink($thisFile);
            } else {
                @rename($thisFile, "$basePath.$i");
            }
        }
    }

    /**
     * Invalidates a cached file with `clearstatcache()` and `opcache_invalidate()`.
     *
     * @param  string  $file  the file path
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
     * @param  string  $path  the file/directory path
     * @param  string|null  $to  the target zip file path. If null, the original path will be used, with ".zip" appended to it.
     * @return string the zip file path
     *
     * @throws InvalidArgumentException if `$path` is not a valid file/directory path
     * @throws RuntimeException if the zip cannot be created
     */
    public static function zip(string $path, ?string $to = null): string
    {
        $path = static::normalizePath($path);

        if (! file_exists($path)) {
            throw new InvalidArgumentException("No file/directory exists at $path");
        }

        if ($to === null) {
            $to = "$path.zip";
        }

        $zip = new ZipArchive;

        if ($zip->open($to, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Cannot create zip at $to");
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
     * @param  ZipArchive  $zip  the ZipArchive object
     * @param  string  $dir  the directory path
     * @param  string|null  $prefix  the path prefix to use when adding the contents of the directory
     * @param  array|string[]  $only  list of glob patterns for file inclusion (e.g. `['*.php', '*.js']`)
     * @param  array|string[]  $except  list of glob patterns for file exclusion (e.g. `['*.log']`)
     * @param  bool  $recursive  whether to search recursively
     */
    public static function addFilesToZip(
        ZipArchive $zip,
        string $dir,
        ?string $prefix = null,
        array $only = [],
        array $except = [],
        bool $recursive = true,
    ): void {
        if (! is_dir($dir)) {
            return;
        }

        if ($prefix !== null) {
            $prefix = static::normalizePath($prefix).'/';
        } else {
            $prefix = '';
        }

        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->files()
            ->in($dir);

        if (! $recursive) {
            $finder->depth('== 0');
        }

        if (! empty($only)) {
            $finder->name($only);
        }

        if (! empty($except)) {
            $finder->notName($except);
        }

        foreach ($finder as $file) {
            // Use forward slashes
            $filePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());
            // Preserve the directory structure within the templates folder
            $zip->addFile($filePath, $prefix.substr($filePath, strlen($dir) + 1));
        }
    }

    /**
     * Return a file extension for the given MIME type.
     *
     * @throws InvalidArgumentException if no known extensions exist for the given MIME type.
     */
    public static function getExtensionByMimeType(string $mimeType): string
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

        $extensions = MimeTypes::getDefault()->getExtensions(strtolower($mimeType));

        if (empty($extensions)) {
            throw new InvalidArgumentException("No file extensions are known for the MIME Type $mimeType.");
        }

        return reset($extensions);
    }

    /**
     * Returns a unique version of a filename with `uniqid()`, ensuring the result is at most 255 characters.
     *
     * @param  string  $baseName  The original filename, or just a file extension prefixed with a `.`.
     */
    public static function uniqueName(string $baseName): string
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

        return uniqid($name, true).$ext;
    }
}
