<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Validation;

use Closure;
use CraftCms\Cms\Mcp\PublicEndpoint;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @since 6.0.0
 */
class PublicEndpointRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match("#^/(?:[A-Za-z0-9._~!$&'()*+,;=:@-]+)(?:/[A-Za-z0-9._~!$&'()*+,;=:@-]+)*$#D", $value)) {
            $fail('The public MCP endpoint must be an app-relative path such as /mcp, without a query, fragment, encoded characters, or route parameters.');

            return;
        }

        foreach (explode('/', ltrim($value, '/')) as $segment) {
            if ($segment === '.' || $segment === '..') {
                $fail('The public MCP endpoint cannot contain dot path segments.');

                return;
            }
        }

        $conflict = app(PublicEndpoint::class)->conflict($value);

        if ($conflict !== null) {
            $fail($conflict);
        }
    }
}
