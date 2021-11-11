<?php

namespace craft\conditions\elements\entry;

use craft\conditions\elements\ElementQueryCondition;

/**
 * Entry query condition.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class EntryQueryCondition extends ElementQueryCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            AuthorGroupConditionRule::class,
            ExpiryDateConditionRule::class,
            PostDateConditionRule::class,
            SectionConditionRule::class,
            TypeConditionRule::class,
        ]);
    }
}
