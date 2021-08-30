<?php

namespace craft\conditions\elements\entry;

use craft\conditions\elements\ElementQueryCondition;
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

    public function getElementType(): string
    {
        return Entry::class;
    }
}