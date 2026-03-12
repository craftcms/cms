<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use Craft;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

readonly class DisallowMb4 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Craft::$app->getDb()->getSupportsMb4() && Str::containsMb4($value)) {
            $fail(t('{attribute} cannot contain emoji.'));
        }
    }
}
