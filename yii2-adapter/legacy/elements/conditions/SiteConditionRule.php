<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Site condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\SiteConditionRule} instead.
     */
    class SiteConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\SiteConditionRule::class, SiteConditionRule::class);
