<?php

namespace craft\elements\conditions\entries;

use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\LevelConditionRule;

/**
 * Entry query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class EntryCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            AuthorConditionRule::class,
            AuthorGroupConditionRule::class,
            ExpiryDateConditionRule::class,
            LevelConditionRule::class,
            PostDateConditionRule::class,
            SavableConditionRule::class,
            SectionConditionRule::class,
            MatrixFieldConditionRule::class,
            TypeConditionRule::class,
            ViewableConditionRule::class,
        ]);
    }
}
