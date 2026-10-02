<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Route\ElementRoute;
use Illuminate\Contracts\Validation\ValidationRule;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ElementRouteRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! ElementRoute::isValid($value)) {
            $fail(t('Enter a named route or a fully qualified controller action.'));
        }
    }
}
