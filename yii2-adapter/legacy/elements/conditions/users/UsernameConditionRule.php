<?php

namespace craft\elements\conditions\users;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Username condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\UsernameConditionRule} instead.
     */
    class UsernameConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\User\Conditions\UsernameConditionRule::class, UsernameConditionRule::class);
