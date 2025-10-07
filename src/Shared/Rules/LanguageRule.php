<?php

namespace CraftCms\Cms\Shared\Rules;

use Closure;
use Craft;
use Illuminate\Contracts\Validation\ValidationRule;

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

        if ($this->onlySiteLanguages && ! in_array($value, Craft::$app->getI18n()->getSiteLocaleIds(), true)) {
            $fail($this->message ?? Craft::t('app', '{value} is not a valid site language.', compact('value')));
        }

        if (! in_array($value, Craft::$app->getI18n()->getAllLocaleIds(), true)) {
            $fail($this->message ?? Craft::t('app', '{value} is not a valid site language.', compact('value')));
        }
    }
}
