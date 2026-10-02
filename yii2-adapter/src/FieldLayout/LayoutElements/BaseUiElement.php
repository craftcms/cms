<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout\LayoutElements;

use CraftCms\Yii2Adapter\FieldLayout\Concerns\LegacyFormNode;

abstract class BaseUiElement extends \CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement
{
    use LegacyFormNode;
}
