<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use CraftCms\Yii2Adapter\FieldLayout\Concerns\LegacyUiNode;

abstract class FieldLayoutElement extends \CraftCms\Cms\FieldLayout\FieldLayoutElement
{
    use LegacyUiNode;
}
