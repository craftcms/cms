<?php

namespace craft\base\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\Contracts\ConditionRuleInterface} instead.
     */
    interface ConditionRuleInterface extends \CraftCms\Cms\Condition\Contracts\ConditionRuleInterface
    {
    }
}

class_alias(\CraftCms\Cms\Condition\Contracts\ConditionRuleInterface::class, ConditionRuleInterface::class);
