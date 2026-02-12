<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ElementConditionInterface defines the common interface to be implemented by element conditions.
     *
     * A base implementation is provided by [[ElementCondition]].
     *
     * @mixin ElementCondition
     * @phpstan-require-extends ElementCondition
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface} instead.
     */
    interface ElementConditionInterface
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface::class, ElementConditionInterface::class);
