<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;

/**
 * Triggered while resolving the action menu items shown on a field in a
 * rendered form.
 *
 * {@see BaseField::resolveActionMenuItems()}
 *
 * Deliberately not an `ElementActionMenuItemsResolving` subclass: the subject
 * is a field within a layout, not the element itself, so listeners for one
 * shouldn't receive the other. `$element` is nullable because a form can be
 * rendered without one — a field layout component's settings screen, for
 * instance.
 */
class FieldLayoutActionMenuItemsResolving
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public ?ElementInterface $element = null,
        public array $items = [],
        public bool $static = false,
        public ?BaseField $fieldLayoutComponent = null,
    ) {}
}
