<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayoutComponent;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Form\Enums\ControlMode;

/**
 * Triggered while resolving a field layout component's action menu items.
 *
 * {@see BaseField::resolveActionMenuItems()}
 */
class FieldLayoutComponentActionMenuItemsResolving
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __construct(
        public FieldLayoutComponent $fieldLayoutComponent,
        public array $items = [],
        public ?ElementInterface $element = null,
        public ControlMode $mode = ControlMode::Editable,
    ) {}
}
