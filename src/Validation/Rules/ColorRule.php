<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

class ColorRule implements ValidationRule
{
    private string $pattern = '/^(?:#[0-9a-f]{6}|transparent)$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $value = self::normalizeColor($value);
        }

        $valid = ! is_array($value) && preg_match($this->pattern, (string) $value);

        if (! $valid) {
            $fail(t('{attribute} is invalid.', [
                'attribute' => $attribute,
            ]));
        }
    }

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

        // #abc => #aabbcc
        if (strlen($color) === 4) {
            return '#'.$color[1].$color[1].$color[2].$color[2].$color[3].$color[3];
        }

        return $color;
    }
}
