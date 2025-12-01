<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use craft\models\FieldLayout;

/**
 * @event FieldLayoutSaved The event that is triggered after a field layout is saved.
 */
final class FieldLayoutSaved extends FieldLayoutEvent
{
    public function __construct(
        FieldLayout $layout,
        public bool $isNew,
    ) {
        parent::__construct($layout);
    }
}
