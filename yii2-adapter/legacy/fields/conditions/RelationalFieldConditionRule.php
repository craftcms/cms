<?php

namespace craft\fields\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Relational field condition rule.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule} instead.
     */
    class RelationalFieldConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule::class, RelationalFieldConditionRule::class);
