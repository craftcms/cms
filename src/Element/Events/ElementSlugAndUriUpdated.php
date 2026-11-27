<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;

/**
 * Dispatched after an element’s slug and URI are written for one site, such as when its parent or its section’s
 * URI format changes.
 *
 * @since 6.0.0
 */
class ElementSlugAndUriUpdated
{
    public function __construct(
        public ElementInterface $element,
        /** The element’s URI in this site before the update */
        public ?string $oldUri = null,
    ) {}

    /**
     * Returns the URL the element was served from before the update, if its URI changed.
     *
     * The URL is built from the old URI the way elements build theirs by default, so it doesn’t reflect
     * {@see ElementUrlResolving} or {@see ElementUrlResolved} handlers.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        if ($this->oldUri === null || $this->oldUri === $this->element->uri) {
            return [];
        }

        $path = $this->oldUri === Element::HOMEPAGE_URI ? '' : $this->oldUri;

        return [Html::encodeSpaces(Url::siteUrl($path, null, null, $this->element->siteId))];
    }
}
