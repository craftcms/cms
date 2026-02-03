<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event DefineUrl The event that is triggered when defining the element's URL.
 *
 * To prevent the element from getting a URL, ensure `$url` is set to `null`,
 * and set `$handled` to `true`.
 *
 * {@see \CraftCms\Cms\Element\Concerns\HasRoutesAndUrls::getUrl()}
 *
 * @since 6.0.0
 */
final class DefineUrl
{
    /**
     * @param  ElementInterface  $element  The element
     * @param  string|null  $url  The URL
     * @param  bool  $handled  Whether the event has been handled
     */
    public function __construct(
        public ElementInterface $element,
        public ?string $url = null,
        public bool $handled = false,
    ) {}
}
