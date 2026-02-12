<?php

namespace craft\elements\conditions\users;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Site condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule} instead.
     */
    class AffiliatedSiteConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule::class, AffiliatedSiteConditionRule::class);
