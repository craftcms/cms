<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Support\Str;

trait PreservesElementRefs
{
    protected function sanitizePreservingElementRefs(string $value, Closure $sanitize): string
    {
        $atToken = 'CRAFT_REF_TAG_AT_'.Str::random(16);
        $value = $this->protectReferenceTagAts($value, $atToken);
        $value = $sanitize($value);

        return str_replace($atToken, '@', $value);
    }

    private function protectReferenceTagAts(string $value, string $atToken): string
    {
        if (! str_contains($value, '{')) {
            return $value;
        }

        $elements = app(Elements::class);

        return preg_replace_callback(
            Elements::REF_TAG_PATTERN,
            function (array $matches) use ($atToken, $elements) {
                if (
                    ! str_contains($matches[0], '@') ||
                    $elements->getElementTypeByRefHandle($matches['elementType']) === null
                ) {
                    return $matches[0];
                }

                return str_replace('@', $atToken, $matches[0]);
            },
            $value,
        ) ?? $value;
    }
}
