<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Element has descendants condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 5.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\HasDescendantsRule} instead.
     */
    class HasDescendantsRule
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\HasDescendantsRule::class, HasDescendantsRule::class);
