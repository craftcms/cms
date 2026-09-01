<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use Illuminate\Container\Attributes\Scoped;

/**
 * Tracks the element matched for the current site
 * request so its resolved route can be reused
 * later in the request lifecycle.
 */
#[Scoped]
class MatchedElement
{
    private ElementInterface|false $element = false;

    /** @var array<mixed>|false */
    private array|false $route = false;

    public static function get(): ElementInterface|false
    {
        return self::instance()->element;
    }

    /**
     * @return array<mixed>|false
     */
    public static function getRoute(): array|false
    {
        return self::instance()->route;
    }

    public static function set(ElementInterface|false|null $element, mixed $route = null): void
    {
        $matchedElement = false;
        $matchedRoute = false;

        if ($element instanceof ElementInterface && $route ??= $element->getRoute()) {
            $matchedElement = $element;
            $matchedRoute = match (true) {
                is_string($route) => [$route, []],
                is_array($route) => $route,
                default => false,
            };
        }

        $state = self::instance();
        $state->element = $matchedElement;
        $state->route = $matchedRoute;
    }

    private static function instance(): self
    {
        return app(self::class);
    }
}
