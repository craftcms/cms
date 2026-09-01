<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade\Directives;

use CraftCms\Cms\View\TemplateHooks;
use Illuminate\View\Compilers\BladeCompiler;

class HookDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftHook', fn (string $expression) => self::compileHook($expression));
    }

    public static function compileHook(string $expression): string
    {
        return sprintf(
            rtrim(<<<'PHP'
<?php
$__craftHookContext = %1$s::templateContext(get_defined_vars());
echo %1$s::render(%2$s, $__craftHookContext);
extract($__craftHookContext, EXTR_OVERWRITE);
unset($__craftHookContext);
?>
PHP),
            self::class,
            $expression,
        );
    }

    /** @param array<string, mixed> $context */
    public static function render(string $hook, array &$context): string
    {
        return app(TemplateHooks::class)->invoke($hook, $context);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public static function templateContext(array $variables): array
    {
        return array_filter(
            $variables,
            fn (string $key): bool => ! str_starts_with($key, '__'),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
