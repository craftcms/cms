<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use craft\base\ElementInterface;
use Illuminate\Support\Facades\Context;

/**
 * Tracks the element matched for the current site
 * request so its resolved route can be reused
 * later in the request lifecycle.
 */
class MatchedElement
{
    public static function get(): ElementInterface|false
    {
        return Context::getHidden('craft.matchedElement', false);
    }

    public static function getRoute(): array|false
    {
        return Context::getHidden('craft.matchedElementRoute', false);
    }

    public static function set(ElementInterface|false|null $element, ?array $route = null): void
    {
        $matchedElement = false;
        $matchedRoute = false;

        if ($element instanceof ElementInterface && $route ??= $element->getRoute()) {
            $matchedElement = $element;
            $matchedRoute = is_string($route) ? [$route, []] : $route;
        }

        Context::addHidden('craft.matchedElement', $matchedElement);
        Context::addHidden('craft.matchedElementRoute', $matchedRoute);
    }
}
