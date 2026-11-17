<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ColorRule implements ValidationRule
{
    /**
     * @param  bool  $allowAlpha  Whether 8-digit hex values with an alpha channel should be allowed.
     */
    public function __construct(
        public readonly bool $allowAlpha = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $value = self::normalizeColor($value);
        }

        $pattern = $this->allowAlpha
            ? '/^(?:#[0-9a-f]{6}(?:[0-9a-f]{2})?|transparent)$/'
            : '/^(?:#[0-9a-f]{6}|transparent)$/';

        $valid = ! is_array($value) && preg_match($pattern, (string) $value);

        if (! $valid) {
            $fail(t('{attribute} is invalid.', [
                'attribute' => $attribute,
            ]));
        }
    }

    /**
     * Normalizes a color value.
     *
     * Shorthand hex values are expanded (`#abc` => `#aabbcc`, `#abcd` => `#aabbccdd`),
     * and fully-opaque alpha channels are removed (`#aabbccff` => `#aabbcc`).
     */
    public static function normalizeColor(string $color): string
    {
        // lowercase
        $color = strtolower($color);

        if ($color === 'transparent') {
            return $color;
        }

        // make sure it starts with a #
        if ($color[0] !== '#') {
            $color = '#'.$color;
        }

        $length = strlen($color);
        if ($length === 4 || $length === 5) {
            $color = '#'.implode('', array_map(fn (string $char) => $char.$char, str_split(substr($color, 1))));
        }

        if (strlen($color) === 9 && str_ends_with($color, 'ff')) {
            $color = substr($color, 0, 7);
        }

        return $color;
    }
}
