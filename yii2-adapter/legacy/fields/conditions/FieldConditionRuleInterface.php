<?php

namespace craft\fields\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * FieldConditionRuleInterface defines the common interface to be implemented by custom fields' query condition rule classes.
     *
     * Classes implementing this interface should also use [[FieldConditionRuleTrait]].
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface} instead.
     */
    interface FieldConditionRuleInterface
    {
    }
}

class_alias(\CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface::class, FieldConditionRuleInterface::class);
