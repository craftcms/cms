<?php

namespace craft\fields\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Empty/not-empty field condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule} instead.
     */
    class EmptyFieldConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule::class, EmptyFieldConditionRule::class);
