<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class DisallowMb4 implements ValidationRule, ValidatorAwareRule
{
    private ?Validator $validator = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! DB::supportsMb4() && Str::containsMb4($value)) {
            $fail(t('{attribute} cannot contain emoji.', [
                'attribute' => $this->validator?->getDisplayableAttribute($attribute) ?? $attribute,
            ]));
        }
    }

    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }
}
