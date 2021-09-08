<?php

namespace craft\conditions\elements\entry;

use craft\conditions\elements\ElementQueryCondition;
use craft\elements\db\EntryQuery;
use yii\db\QueryInterface;

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
            TypeConditionRule::class,
            SectionConditionRule::class,
            Slug::class,
            AuthorGroupConditionRule::class,
        ];
    }

    /**
     * Modifies a given entry query based on the configured condition rules.
     *
     * @param EntryQuery $query
     * @return void
     */
    public function modifyQuery(QueryInterface $query): void{

    }
}
