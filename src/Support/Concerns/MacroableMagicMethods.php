<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Concerns;

use Illuminate\Support\Traits\Macroable;

trait MacroableMagicMethods
{
    private function resolveMagicMethod(string $prefix, string $name): ?string
    {
        $method = $prefix.$name;

        if (method_exists($this, $method)) {
            return $method;
        }

        if (! static::supportsMacroableMagicMethods()) {
            return null;
        }

        if (static::macroMethodExists($method)) {
            return $method;
        }

        $normalizedMethod = $prefix.ucfirst($name);

        if ($normalizedMethod !== $method && static::macroMethodExists($normalizedMethod)) {
            return $normalizedMethod;
        }

        return null;
    }

    private static function supportsMacroableMagicMethods(): bool
    {
        static $supports = [];

        $class = static::class;

        if (array_key_exists($class, $supports)) {
            return $supports[$class];
        }

        $usesRecursive = class_uses_recursive($class);

        $supports[$class] = in_array(Macroable::class, $usesRecursive, true) && is_callable([$class, 'hasMacro']);

        return $supports[$class];
    }

    private static function macroMethodExists(string $method): bool
    {
        return (bool) call_user_func([static::class, 'hasMacro'], $method);
    }
}
