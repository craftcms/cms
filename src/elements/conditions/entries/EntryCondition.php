<?php

namespace craft\elements\conditions\entries;

use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\LevelConditionRule;
use craft\elements\Entry;

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
    public ?string $elementType = Entry::class;

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            AuthorConditionRule::class,
            AuthorGroupConditionRule::class,
            EditableConditionRule::class,
            ExpiryDateConditionRule::class,
            LevelConditionRule::class,
            PostDateConditionRule::class,
            SectionConditionRule::class,
            TypeConditionRule::class,
        ]);
    }
}
