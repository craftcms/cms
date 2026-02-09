<?php

use CraftCms\Cms\FieldLayout\FieldLayout;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * FieldLayoutElement is the base class for classes representing field layout elements in terms of objects.
     *
     * @property FieldLayout $layout The layout this element belongs to
     * @since 3.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutElement} instead.
     */
    class FieldLayoutElement
    {
    }
}

class_alias(\CraftCms\Cms\FieldLayout\FieldLayoutElement::class, FieldLayoutElement::class);
