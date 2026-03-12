<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Concerns\HasSources;

/**
 * @event RegisterSources The event that is triggered when registering the available sources for the element type.
 *
 * {@see HasSources::sources()}
 */
class RegisterSources
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $context  The context ('index', 'modal', 'field', or 'settings')
     * @param  array  $sources  The registered sources
     */
    public function __construct(
        public string $elementType,
        public string $context,
        public array $sources = [],
    ) {}
}
