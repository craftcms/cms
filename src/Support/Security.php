<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Support\Facades\Path;
use Illuminate\Container\Attributes\Singleton;
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
     * Returns whether the given file path is located within or above any system directories.
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
}
