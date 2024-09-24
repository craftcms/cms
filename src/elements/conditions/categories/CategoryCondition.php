<?php

namespace craft\elements\conditions\categories;

use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\HasDescendantsRule;
use craft\elements\conditions\LevelConditionRule;

/**
 * Category query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CategoryCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            GroupConditionRule::class,
            HasDescendantsRule::class,
            LevelConditionRule::class,
        ]);
    }
}
