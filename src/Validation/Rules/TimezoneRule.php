<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Support\Env;
use Illuminate\Contracts\Validation\ValidationRule;
use IntlDateFormatter;
use IntlException;

readonly class TimezoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $timezone = Env::parse($value);

        try {
            $formatter = new IntlDateFormatter(app()->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE);

            if (! $formatter->setTimeZone($timezone)) {
                $fail('The time zone does not appear to be supported by ICU.');
            }
        } catch (IntlException) {
            $fail('The time zone does not appear to be supported by ICU: '.intl_get_error_message());
        }
    }
}
