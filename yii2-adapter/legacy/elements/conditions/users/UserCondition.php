<?php

namespace craft\elements\conditions\users;

use CraftCms\Cms\Element\Conditions\ElementCondition;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * User query condition.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\UserCondition} instead.
     */
    class UserCondition extends ElementCondition
    {
    }
}

class_alias(\CraftCms\Cms\User\Conditions\UserCondition::class, UserCondition::class);
