<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasRoutesAndUrls;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @event SetRoute The event that is triggered when defining the route that should be
 * used when this element's URL is requested.
 *
 * Set `$handled` to `true` to explicitly tell the element that a route has been set
 * (even if you're setting it to `null`).
 *
 * {@see HasRoutesAndUrls::getRoute()}
 */
final class SetRoute
{
    use HandleableEvent;

    /**
     * @param  ElementInterface  $element  The element
     * @param  mixed  $route  The route that should be used for the element, or `null` if no special action should be taken
     */
    public function __construct(
        public ElementInterface $element,
        public mixed $route = null,
    ) {}
}
