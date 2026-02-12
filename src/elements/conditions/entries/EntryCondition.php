<?php

namespace craft\elements\conditions\entries;

use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\HasDescendantsRule;
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
            HasDescendantsRule::class,
            LevelConditionRule::class,
            PostDateConditionRule::class,
            SavableConditionRule::class,
            SectionConditionRule::class,
            FieldConditionRule::class,
            TypeConditionRule::class,
            ViewableConditionRule::class,
        ]);
    }
}
