<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * ID condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\UriConditionRule} instead.
     */
    class UriConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\UriConditionRule::class, UriConditionRule::class);
