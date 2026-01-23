<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

/**
 * Validates that a username does not contain whitespace.
 */
final class UsernameRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (preg_match('/\s+/', (string) $value)) {
            $fail(t('{attribute} cannot contain spaces.'));
        }
    }
}
