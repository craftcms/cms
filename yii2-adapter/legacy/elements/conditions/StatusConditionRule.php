<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Element status condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\StatusConditionRule} instead.
     */
    class StatusConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\StatusConditionRule::class, StatusConditionRule::class);
