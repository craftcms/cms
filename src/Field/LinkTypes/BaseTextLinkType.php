<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;

/**
 * Base text link type.
 *
 * @since 6.0.0
 */
abstract class BaseTextLinkType extends BaseLinkType
{
    /**
     * Returns the prefix(es) that supported URLs must start with.
     *
     * @return string|string[]
     */
    abstract protected function urlPrefix(): string|array;

    public function supports(string $value): bool
    {
        $value = mb_strtolower($value);

        return array_any((array) $this->urlPrefix(), fn ($prefix) => str_starts_with($value, (string) $prefix));
    }

    #[\Override]
    public function normalizeValue(string $value): string
    {
        if (str_contains($value, ':') || $this->supports($value)) {
            return $value;
        }

        // Only add a prefix if the end result validates
        $prefix = Arr::first((array) $this->urlPrefix());
        $normalized = "$prefix$value";

        return $this->validateValue($normalized) ? $normalized : $value;
    }

    public function linkLabel(string $value): string
    {
        foreach ((array) $this->urlPrefix() as $prefix) {
            $value = Str::chopStart($value, $prefix);
        }
        if (preg_match('/^[^\/]+\/$/', $value)) {
            return rtrim($value, '/');
        }

        return $value;
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     kind: string,
     *     prefixes: list<string>,
     *     pattern: string,
     *     inputAttributes: array<string, string>,
     * }
     */
    #[\Override]
    public function pickerConfig(): array
    {
        return array_merge(parent::pickerConfig(), [
            'kind' => 'text',
            'prefixes' => (array) $this->urlPrefix(),
            'pattern' => $this->pattern(),
            'inputAttributes' => $this->inputAttributes(),
        ]);
    }

    /**
     * Returns any additional attributes that should be set ot the text input.
     *
     * @return array<string, string>
     */
    protected function inputAttributes(): array
    {
        return [];
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        $pattern = sprintf('/%s/i', $this->pattern());

        return (bool) preg_match($pattern, $value);
    }

    /**
     * Returns the regular expression pattern (sans delimiters) that should be used to validate link values.
     */
    protected function pattern(): string
    {
        $prefixes = array_map(fn (string $prefix) => preg_quote($prefix, '/'), (array) $this->urlPrefix());

        return sprintf('^(%s)', implode('|', $prefixes));
    }
}
