<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

final readonly class LanguageRule implements ValidationRule
{
    public function __construct(
        private bool $onlySiteLanguages = true,
        private ?string $message = null,
        private ?bool $parseValue = false,
    ) {}

    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->parseValue && Str::startsWith($value, '$')) {
            $value = Env::parse($value);
        }

        if (! $value) {
            return;
        }

        if ($this->onlySiteLanguages && I18N::getSiteLocaleIds()->doesntContain($value)) {
            $fail($this->message ?? t('{value} is not a valid site language.', compact('value')));
        }

        if (I18N::getAllLocaleIds()->doesntContain($value)) {
            $fail($this->message ?? t('{value} is not a valid site language.', compact('value')));
        }
    }
}
