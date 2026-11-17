<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use Illuminate\Http\Request;
use League\Uri\Http;

/**
 * A redirect rule from `config/redirects.php`, applied to requests that would otherwise 404.
 *
 * @since 6.0.0
 */
readonly class RedirectRule
{
    public function __construct(
        public string $from,
        public string $to = '',
        public int $statusCode = 302,
        public bool $caseSensitive = false,
        public ?Closure $match = null,
    ) {}

    /**
     * Creates a rule from a `config/redirects.php` item: a “to” URI keyed by its “from” URI, or an
     * array with `from`, `to`, `statusCode`, `caseSensitive`, and `match` keys.
     *
     * @param  string|array{from?: string, to?: string, statusCode?: int, caseSensitive?: bool, match?: callable}  $rule
     */
    public static function fromConfig(int|string $from, string|array $rule): self
    {
        if (is_string($rule)) {
            return new self((string) $from, $rule);
        }

        return new self(
            from: $rule['from'] ?? (string) $from,
            to: $rule['to'] ?? '',
            statusCode: $rule['statusCode'] ?? 302,
            caseSensitive: $rule['caseSensitive'] ?? false,
            match: isset($rule['match']) ? Closure::fromCallable($rule['match']) : null,
        );
    }

    /**
     * Returns the URL the request should redirect to, or `null` if the rule doesn’t match it.
     */
    public function getMatch(Request $request): ?string
    {
        if ($this->match !== null) {
            return ($this->match)(Http::new($request->fullUrl()));
        }

        $subject = trim($request->decodedPath(), '/');

        if (str_contains($this->from, '<')) {
            if (! preg_match($this->toRegexPattern(), $subject, $matches)) {
                return null;
            }

            return strtr($this->to, collect($matches)->mapWithKeys(fn ($value, $key) => ["<$key>" => $value])->all());
        }

        $comparison = $this->caseSensitive ? strcmp($this->from, $subject) : strcasecmp($this->from, $subject);

        return $comparison === 0 ? $this->to : null;
    }

    private function toRegexPattern(): string
    {
        $slugChars = ['.', '_', '-'];
        $slugWordSeparator = Cms::config()->slugWordSeparator;

        if ($slugWordSeparator !== '/' && ! in_array($slugWordSeparator, $slugChars, true)) {
            $slugChars[] = $slugWordSeparator;
        }

        $regexTokens = [
            '{handle}' => sprintf('(?:%s)', HandleRule::$handlePattern),
            '{slug}' => sprintf('(?:[\p{L}\p{N}\p{M}%s]+)', preg_quote(implode('', $slugChars), '/')),
            '{uid}' => sprintf('(?:%s)', Str::uuidPattern()),
        ];

        // Tokenize the params first, so only regex characters outside of them are escaped
        $paramPatterns = [];
        $pattern = preg_replace_callback('/<([\w._-]+):?([^>]+)?>/', function (array $match) use (&$paramPatterns, $regexTokens): string {
            $name = $match[1];
            $paramPatterns["<$name>"] = sprintf('(?P<%s>%s)', $name, strtr($match[2] ?? '[^\/]+', $regexTokens));

            return "<$name>";
        }, $this->from);

        $pattern = strtr((string) $pattern, [
            ...$paramPatterns,
            '.' => '\\.',
            '*' => '\\*',
            '$' => '\\$',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
        ]);

        return sprintf('`^%s$`%s', $pattern, $this->caseSensitive ? 'u' : 'iu');
    }
}
