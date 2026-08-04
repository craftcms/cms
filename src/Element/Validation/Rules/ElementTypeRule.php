<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Validation\Rules;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use Illuminate\Contracts\Validation\ValidationRule;
use Stringable;

class ElementTypeRule implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        return is_string($value) && ComponentHelper::validateComponentClass($value, ElementInterface::class);
    }

    public static function message(mixed $value): string
    {
        $class = is_scalar($value) || $value instanceof Stringable
            ? (string) $value
            : get_debug_type($value);

        return new InvalidTypeException($class, ElementInterface::class)->getMessage();
    }

    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::isValid($value)) {
            return;
        }

        $fail(self::message($value));
    }
}
