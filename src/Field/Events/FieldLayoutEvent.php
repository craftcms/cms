<?php

namespace CraftCms\Cms\Field\Events;

use craft\models\FieldLayout;

abstract class FieldLayoutEvent
{
    public function __construct(
        public FieldLayout $layout,
    ) {}
}
