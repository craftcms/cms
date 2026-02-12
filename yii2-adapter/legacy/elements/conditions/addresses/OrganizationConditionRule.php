<?php

namespace craft\elements\conditions\addresses;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Address organization condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\OrganizationConditionRule} instead.
     */
    class OrganizationConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Address\Conditions\OrganizationConditionRule::class, OrganizationConditionRule::class);
