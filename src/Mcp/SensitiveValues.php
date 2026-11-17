<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Support\Security;
use Mcp\Exception\ToolCallException;
use SensitiveParameter;

/**
 * Keeps credentials out of MCP results.
 *
 * Values under keys that look sensitive are redacted with {@see Security::redactIfSensitive()}, environment
 * variable references included. Clients can send a redacted value back to keep the saved value.
 *
 * @since 6.0.0
 */
readonly class SensitiveValues
{
    public function __construct(private Security $security) {}

    /**
     * Redacts sensitive values within the given value.
     *
     * @param  string  $key  The key or project config path the value is stored under.
     */
    public function redact(#[SensitiveParameter] mixed $value, string $key = ''): mixed
    {
        return $this->security->redactIfSensitive($key, $value);
    }

    /**
     * Redacts sensitive values from YAML text, such as a project config diff.
     */
    public function redactYaml(#[SensitiveParameter] string $yaml): string
    {
        return preg_replace_callback(
            '/^([-+ ]?\s*(?:-\s+)?)([\w-]+)(:[ \t]+)(.+)$/m',
            fn (array $match): string => $this->security->isSensitive($match[2])
                ? $match[1].$match[2].$match[3].str_repeat('•', mb_strlen(trim($match[4], " \t'\"")))
                : $match[0],
            $yaml,
        ) ?? $yaml;
    }

    /**
     * Swaps redacted values in supplied values for the saved values they stand in for.
     *
     * @param  array<array-key, mixed>  $supplied
     * @param  array<array-key, mixed>  $current
     * @return array<array-key, mixed>
     *
     * @throws ToolCallException if a redacted value doesn’t stand in for a saved value.
     */
    public function restore(#[SensitiveParameter] array $supplied, #[SensitiveParameter] array $current, string $path = ''): array
    {
        foreach ($supplied as $key => &$value) {
            $keyPath = ltrim("$path.$key", '.');

            if (is_array($value)) {
                $value = $this->restore($value, is_array($current[$key] ?? null) ? $current[$key] : [], $keyPath);
            } elseif (is_string($value) && $this->isRedacted($value)) {
                if (! is_string($current[$key] ?? null)) {
                    throw new ToolCallException("$keyPath is redacted, so it can only keep its saved value. Provide the actual value or an environment variable reference, such as \$MY_SECRET.");
                }

                $value = $current[$key];
            }
        }

        return $supplied;
    }

    private function isRedacted(string $value): bool
    {
        return (bool) preg_match('/^•+$/u', $value);
    }
}
