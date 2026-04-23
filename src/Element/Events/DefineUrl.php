<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasRoutesAndUrls;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @event DefineUrl The event that is triggered when defining the element's URL.
 *
 * To prevent the element from getting a URL, ensure `$url` is set to `null`,
 * and set `$handled` to `true`.
 *
 * {@see HasRoutesAndUrls::getUrl()}
 */
class DefineUrl
{
    use HandleableEvent;

    /**
     * @param  ElementInterface  $element  The element
     * @param  string|null  $url  The URL
     */
    public function __construct(
        public ElementInterface $element,
        public ?string $url = null,
    ) {}
}
