<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Support\Facades\Path;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Request;
use SensitiveParameter;

#[Singleton]
class Security
{
    public function __construct(
        /**
         * @var string[] Keywords used to reference sensitive data
         *
         * @see redactIfSensitive()
         */
        public private(set) array $sensitiveKeywords = [
            'key',
            'pass',
            'password',
            'pw',
            'secret',
            'sk',
            'tok',
            'token',
        ],
    ) {
        // normalize the sensitive keywords
        $this->sensitiveKeywords = array_map(
            fn (string $word) => Str::camel2words($word, false),
            $this->sensitiveKeywords,
        );
    }

    /**
     * Returns whether the given key appears to be sensitive.
     */
    public function isSensitive(string $key): bool
    {
        if (empty($this->sensitiveKeywords)) {
            return true;
        }

        return (bool) preg_match('/\b('.implode('|', $this->sensitiveKeywords).')\b/', Str::camel2words($key, false));
    }

    /**
     * Checks the given key to see if it looks like it contains sensitive info, and if so, redacts the given value.
     */
    public function redactIfSensitive(string $key, #[SensitiveParameter] mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $n => &$v) {
                $v = $this->redactIfSensitive((string) $n, $v);
            }
        } elseif (is_string($value) && $this->isSensitive($key)) {
            $value = str_repeat('•', mb_strlen($value));
        }

        return $value;
    }

    /**
     * Returns whether the given file path is located within or above any Craft-specific system directories.
     */
    public function isSystemDir(string $path): bool
    {
        $path = File::absolutePath($path, '/');

        foreach (Path::system() as $dir) {
            $dir = File::absolutePath($dir, '/');

            if (str_starts_with("$path/", "$dir/") || str_starts_with("$dir/", "$path/")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the given file path is located in what's considered a sensitive/restricted directory.
     */
    public function isRestrictedDir(string $path): bool
    {
        $path = File::absolutePath($path, '/');

        // is it located within a Craft-specific system directory
        if ($this->isSystemDir($path)) {
            return true;
        }

        // is it located within a sensitive os directory
        // windows-based sensitive directories
        if (PHP_OS_FAMILY === 'Windows') {
            // is it located directly in the filesystem's root (e.g. C:\myfile.json, \\server\sharename\myfile.json)
            // we're using forward slashes cause File::absolutePath() already normalized this path
            if (preg_match('#^(?:[A-Za-z]:/[^/]+|//[^/]+/[^/]+/[^/]+)$#', $path)) {
                return true;
            }

            $winRoot = File::normalizePath(Request::server('SystemRoot') ?? 'C:\\Windows', '/');
            $drive = substr($winRoot, 0, 3); // e.g. "C:/" because we've normalized it
            $sensitiveDirs = [
                $winRoot, // C:/Windows
                $drive.'Program Files',
                $drive.'Program Files (x86)',
                $drive.'ProgramData',
            ];
        } else {
            // is it located directly in the filesystem's root (e.g. /myfile.json)
            $parent = dirname($path);
            if ($parent === dirname($parent)) {
                return true;
            }

            // non-windows-based sensitive directories
            $sensitiveDirs = [
                '/boot',
                '/dev',
                '/etc',
                '/proc',
                '/root',
                '/sys',
            ];
        }

        return array_any($sensitiveDirs, fn ($dir) => $path === $dir || str_starts_with("$path/", "$dir/"));
    }
}
