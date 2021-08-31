<?php

namespace craft\conditions\elements\entry;

use craft\conditions\elements\ElementQueryCondition;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;

/**
 *
 */
class EntryQueryCondition extends ElementQueryCondition
{
    /**
     * @inheritDoc
     */
    protected function defineConditionRuleTypes(): array
    {
        return [
            SectionAndEntryTypeConditionRule::class,
            SectionConditionRuleBase::class,
            Slug::class
        ];
    }

    public function getElementQuery(): ElementQuery
    {
        return Entry::find();
    }
}