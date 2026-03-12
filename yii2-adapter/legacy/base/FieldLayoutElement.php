<?php

namespace craft\base;

use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * FieldLayoutElement is the base class for classes representing field layout elements in terms of objects.
 *
 * @property FieldLayout $layout The layout this element belongs to
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutElement} instead.
 */
abstract class FieldLayoutElement extends \CraftCms\Cms\FieldLayout\FieldLayoutElement
{
    use \craft\base\LegacyEventConstants;
}
