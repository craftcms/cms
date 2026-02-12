<?php

namespace craft\elements\conditions;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Relation condition rule.
     *
     * @property int[] $elementIds
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Conditions\RelatedToConditionRule} instead.
     */
    class RelatedToConditionRule
    {
    }
}

class_alias(\CraftCms\Cms\Element\Conditions\RelatedToConditionRule::class, RelatedToConditionRule::class);
