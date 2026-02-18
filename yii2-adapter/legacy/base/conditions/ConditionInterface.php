<?php

namespace craft\base\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\Contracts\ConditionInterface} instead.
     */
    interface ConditionInterface extends \CraftCms\Cms\Condition\Contracts\ConditionInterface
    {
    }
}

class_alias(\CraftCms\Cms\Condition\Contracts\ConditionInterface::class, ConditionInterface::class);
