<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

readonly class ElementUriRule implements ValidationRule
{
    private const string URI_PATTERN = '/^\S+$/u';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($attribute !== 'uri') {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! preg_match(self::URI_PATTERN, (string) $value)) {
            $fail(t('{attribute} is not a valid URI', ['attribute' => $attribute]));
        }
    }
}
