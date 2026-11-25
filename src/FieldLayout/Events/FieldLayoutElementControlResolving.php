<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;

/**
 * Triggered when a field layout field doesn’t provide a UI control of its own.
 *
 * Set `control` to have it rendered within the field’s label and instructions, or `node` to replace the field
 * entirely.
 *
 * {@see BaseField::uiNode()}
 *
 * @since 6.0.0
 */
class FieldLayoutElementControlResolving
{
    public function __construct(
        public BaseField $layoutElement,
        public FieldLayoutElementContext $context,
        public ?Control $control = null,
        public ?Node $node = null,
    ) {}
}
