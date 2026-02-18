<?php

namespace craft\elements\conditions\entries;

use CraftCms\Cms\Element\Conditions\ElementCondition;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Entry query condition.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Conditions\EntryCondition} instead.
     */
    class EntryCondition extends ElementCondition
    {
    }
}

class_alias(\CraftCms\Cms\Entry\Conditions\EntryCondition::class, EntryCondition::class);
