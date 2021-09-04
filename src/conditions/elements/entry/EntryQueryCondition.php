<?php

namespace craft\conditions\elements\entry;

use craft\conditions\elements\ElementQueryCondition;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;

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
        return [
            EntryTypeConditionRule::class,
            EntrySectionConditionRule::class,
            Slug::class,
            AuthorGroupConditionRule::class,
        ];
    }

    public function getElementQuery(): ElementQueryInterface
    {
        return Entry::find();
    }
}
