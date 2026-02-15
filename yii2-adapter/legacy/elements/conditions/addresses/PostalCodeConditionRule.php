<?php

namespace craft\elements\conditions\addresses;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Address postal code condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\PostalCodeConditionRule} instead.
     */
    class PostalCodeConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Address\Conditions\PostalCodeConditionRule::class, PostalCodeConditionRule::class);
