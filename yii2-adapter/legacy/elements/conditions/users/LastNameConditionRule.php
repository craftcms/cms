<?php

namespace craft\elements\conditions\users;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Last name condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\LastNameConditionRule} instead.
     */
    class LastNameConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\User\Conditions\LastNameConditionRule::class, LastNameConditionRule::class);
