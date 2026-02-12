<?php

namespace craft\elements\conditions\addresses;

use craft\elements\conditions\ElementCondition;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Asset query condition.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\AddressCondition} instead.
     */
    class AddressCondition extends ElementCondition
    {
    }
}

class_alias(\CraftCms\Cms\Address\Conditions\AddressCondition::class, AddressCondition::class);
