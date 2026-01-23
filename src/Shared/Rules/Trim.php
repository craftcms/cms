<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class Trim implements ValidationRule
{
    public function __construct(
        private object $object,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $this->object->{$attribute} = trim($value);
    }
}
