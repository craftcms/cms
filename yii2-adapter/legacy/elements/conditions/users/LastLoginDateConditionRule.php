<?php

namespace craft\elements\conditions\users;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Last login date condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\LastLoginDateConditionRule} instead.
     */
    class LastLoginDateConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\User\Conditions\LastLoginDateConditionRule::class, LastLoginDateConditionRule::class);
