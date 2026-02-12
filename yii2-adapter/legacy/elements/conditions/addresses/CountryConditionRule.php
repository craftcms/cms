<?php

namespace craft\elements\conditions\addresses;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Address country condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\CountryConditionRule} instead.
     */
    class CountryConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Address\Conditions\CountryConditionRule::class, CountryConditionRule::class);
