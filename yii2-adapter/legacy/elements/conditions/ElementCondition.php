<?php

namespace craft\elements\conditions;

use craft\base\conditions\BaseCondition;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ElementCondition provides an element condition.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\ElementCondition} instead.
     */
    class ElementCondition extends BaseCondition
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\ElementCondition::class, ElementCondition::class);
