<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Concerns\LabelsAttributes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class DisallowMb4 implements ValidationRule, ValidatorAwareRule
{
    use LabelsAttributes;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! DB::supportsMb4() && Str::containsMb4($value)) {
            $fail(t('{attribute} cannot contain emoji.', [
                'attribute' => $this->attributeLabel($attribute),
            ]));
        }
    }
}
