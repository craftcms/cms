<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use CraftCms\Cms\FieldLayout\FieldLayoutElement;

class FieldLayoutFormElement
{
    public function __construct(
        public FieldLayoutElement $layoutElement,
        public bool $isConditional,
        public string|bool $html,
        public bool $isStatic,
    ) {
    }
}
