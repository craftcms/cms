<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use CraftCms\Cms\Edition;
use Illuminate\Contracts\Validation\ValidationRule;

class RequiresEditionRule implements ValidationRule
{
    public function __construct(
        private readonly Edition $requiredEdition
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Edition::require($this->requiredEdition);
        } catch (Edition\Exceptions\WrongEditionException $e) {
            $fail($e->getMessage());
        }
    }
}
