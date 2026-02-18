<?php

namespace craft\elements\conditions\addresses;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Address sorting code condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Address\Conditions\SortingCodeConditionRule} instead.
     */
    class SortingCodeConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Address\Conditions\SortingCodeConditionRule::class, SortingCodeConditionRule::class);
