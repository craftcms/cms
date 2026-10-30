<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Validation;

use Closure;
use CraftCms\Cms\Mcp\EndpointConflicts;
use CraftCms\Cms\Mcp\Settings;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @since 6.0.0
 */
readonly class EndpointRule implements ValidationRule
{
    public function __construct(private bool $public = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match("#^/(?:[A-Za-z0-9._~!$&'()*+,;=:@-]+)(?:/[A-Za-z0-9._~!$&'()*+,;=:@-]+)*$#D", $value)) {
            $fail('The MCP endpoint must be an app-relative path such as /mcp, without a query, fragment, encoded characters, or route parameters.');

            return;
        }

        foreach (explode('/', ltrim($value, '/')) as $segment) {
            if ($segment === '.' || $segment === '..') {
                $fail('The MCP endpoint cannot contain dot path segments.');

                return;
            }
        }

        if (str_starts_with($value.'/', '/.well-known/')) {
            $fail('The MCP endpoint cannot be within /.well-known.');

            return;
        }

        if ($this->public && app(Settings::class)->isCpRoute($value)) {
            $fail('The public MCP endpoint cannot be within the control panel.');

            return;
        }

        $conflict = app(EndpointConflicts::class)->find($value);

        if ($conflict !== null) {
            $fail($conflict);
        }
    }
}
