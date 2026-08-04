<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

readonly class DisallowMb4 implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! DB::supportsMb4() && Str::containsMb4($value)) {
            $fail(t('{attribute} cannot contain emoji.'));
        }
    }
}
