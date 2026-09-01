<?php

declare(strict_types=1);

namespace CraftCms\Cms\Blade\Directives;

use CraftCms\Cms\Support\Template;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\View\Compilers\BladeCompiler;
use InvalidArgumentException;

class ResourceDirective
{
    public static function register(BladeCompiler $blade): void
    {
        $blade->directive('craftCss', fn (string $expression) => self::compileResource('css', $expression));
        $blade->directive('endCraftCss', fn (string $expression = '') => sprintf('<?php %s::end(%s); ?>', self::class, var_export('css', true)));
        $blade->directive('craftJs', fn (string $expression) => self::compileResource('js', $expression));
        $blade->directive('endCraftJs', fn (string $expression = '') => sprintf('<?php %s::end(%s); ?>', self::class, var_export('js', true)));
        $blade->directive('craftHtml', fn (string $expression) => self::compileResource('html', $expression));
        $blade->directive('endCraftHtml', fn (string $expression = '') => sprintf('<?php %s::end(%s); ?>', self::class, var_export('html', true)));
        $blade->directive('craftScript', fn (string $expression) => self::compileResource('script', $expression));
        $blade->directive('endCraftScript', fn (string $expression = '') => sprintf('<?php %s::end(%s); ?>', self::class, var_export('script', true)));
    }

    public static function compileResource(string $method, string $expression): string
    {
        if (trim($expression) === '') {
            return '<?php ob_start(); ?>';
        }

        return match ($method) {
            'css' => sprintf('<?php \%s::css(%s); ?>', Template::class, $expression),
            'js' => sprintf('<?php \%s::js(%s); ?>', Template::class, $expression),
            default => sprintf('<?php %s::%s(%s); ?>', self::class, $method, $expression),
        };
    }

    public static function html(string $html, int|Position $position = Position::BodyEnd, ?string $key = null): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        app(HtmlStack::class)->html($html, $position, $key);
    }

    /** @param array<string, mixed> $options */
    public static function script(string $script, int|Position $position = Position::BodyEnd, array $options = [], ?string $key = null): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        app(HtmlStack::class)->script($script, $position, $options, $key);
    }

    public static function end(string $method): void
    {
        $content = ob_get_clean();

        match ($method) {
            'css' => Template::css($content),
            'js' => Template::js($content),
            'html' => self::html($content),
            'script' => self::script($content),
            default => throw new InvalidArgumentException("Unknown Craft Blade resource directive [{$method}]."),
        };
    }
}
