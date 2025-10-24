<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

final readonly class LanguageRule implements ValidationRule
{
    public function __construct(
        private bool $onlySiteLanguages = true,
        private ?string $message = null
    ) {}

    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        if ($this->onlySiteLanguages && ! I18N::getSiteLocaleIds()->contains($value)) {
            $fail($this->message ?? t('{value} is not a valid site language.', compact('value')));
        }

        if (! I18N::getAllLocaleIds()->contains($value)) {
            $fail($this->message ?? t('{value} is not a valid site language.', compact('value')));
        }
    }
}
