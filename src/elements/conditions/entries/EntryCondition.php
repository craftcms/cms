<?php

namespace craft\elements\conditions\entries;

use craft\elements\conditions\ElementCondition;

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
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            AuthorConditionRule::class,
            AuthorGroupConditionRule::class,
            ExpiryDateConditionRule::class,
            PostDateConditionRule::class,
            SectionConditionRule::class,
            TypeConditionRule::class,
        ]);
    }
}
