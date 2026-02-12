<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ElementConditionRuleInterface defines the common interface to be implemented by element condition rule classes.
     * A class implementing this interface should also use [[ElementConditionRuleTrait]].
     *
     * @property-read string[] $exclusiveQueryParams The query param names that this rule should have exclusive control over
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface} instead.
     */
    interface ElementConditionRuleInterface
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface::class, ElementConditionRuleInterface::class);
