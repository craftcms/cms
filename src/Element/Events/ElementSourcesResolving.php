<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasSources;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementSourcesResolving The event that is triggered when registering the available sources for the element type.
 *
 * {@see HasSources::sources()}
 */
class ElementSourcesResolving
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $context  The context ('index', 'modal', 'field', or 'settings')
     * @param  list<array<string, mixed>>  $sources  The registered sources
     */
    public function __construct(
        public string $elementType,
        public string $context,
        public array $sources = [],
    ) {}
}
