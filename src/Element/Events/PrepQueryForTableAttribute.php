<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

/**
 * PrepQueryForTableAttribute event is triggered when preparing an element query for a table attribute.
 *
 * If `handled` is set to `true`, the default query preparation will be skipped.
 */
class PrepQueryForTableAttribute
{
    /**
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  ElementQueryInterface  $query  The element query
     * @param  string  $attribute  The attribute name
     * @param  bool  $handled  Whether the event has been handled
     */
    public function __construct(
        public string $elementType,
        public ElementQueryInterface $query,
        public string $attribute,
        public bool $handled = false,
    ) {}
}
