<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Illuminate\Container\Attributes\Scoped;

#[Scoped]
final class TemplateHooks
{
    /** @var array<string, list<callable>> */
    private array $hooks = [];

    /**
     * Registers a hook handler.
     *
     * When a `{% hook "name" %}` tag is rendered, all handlers registered
     * for that name will be called in order. Each handler receives the
     * current Twig context by reference and may return HTML to output.
     *
     * @param  string  $hook  The hook name.
     * @param  callable|class-string<object&callable>  $handler  Callback: fn(array &$context, bool &$handled): ?string
     * @param  bool  $append  Whether to append (true) or prepend (false) the handler.
     */
    public function register(string $hook, callable|string $handler, bool $append = true): void
    {
        if ($append || empty($this->hooks[$hook])) {
            $this->hooks[$hook][] = $handler;
        } else {
            array_unshift($this->hooks[$hook], $handler);
        }
    }

    /**
     * Invokes all handlers registered for the given hook.
     *
     * Each handler receives the template context by reference (so it can
     * mutate variables available to the template) and a `$handled` flag.
     * Setting `$handled = true` stops further handlers from executing.
     * Return values from all invoked handlers are concatenated.
     *
     * @param  string  $hook  The hook name.
     * @param  array  $context  The current template context (passed by reference).
     * @return string Concatenated output from all invoked handlers.
     */
    public function invoke(string $hook, array &$context): string
    {
        $return = '';

        /** @var bool - Needed because PHPstan will otherwise type it as `false` */
        $handled = false;

        foreach ($this->hooks[$hook] ?? [] as $handler) {
            if (is_string($handler)) {
                $handler = app()->make($handler);
            }

            $return .= $handler($context, $handled);

            if ($handled) {
                break;
            }
        }

        return $return;
    }
}
