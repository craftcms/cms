<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ElementCondition provides an element condition.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\ElementCondition} instead.
     */
    class ElementCondition
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\ElementCondition::class, ElementCondition::class);
