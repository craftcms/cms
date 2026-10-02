<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use CraftCms\Yii2Adapter\FieldLayout\Concerns\LegacyFormNode;

abstract class FieldLayoutElement extends \CraftCms\Cms\FieldLayout\FieldLayoutElement
{
    use LegacyFormNode;
}
