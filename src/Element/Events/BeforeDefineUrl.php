<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasRoutesAndUrls;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @event BeforeDefineUrl The event that is triggered before defining the element's URL.
 *
 * It can be used to provide a custom URL, completely bypassing the default URL generation.
 *
 * To prevent the element from getting a URL, ensure `$url` is set to `null`,
 * and set `$handled` to `true`.
 *
 * Note that DefineUrl will still be called regardless of what happens with this event.
 *
 * {@see HasRoutesAndUrls::getUrl()}
 */
class BeforeDefineUrl
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
