<?php

namespace craft\conditions;

use yii\db\QueryInterface;

/**
 * BaseQueryCondition provides a base implementation for conditions that modify a database query.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseQueryCondition extends BaseCondition implements QueryConditionInterface
{
    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var QueryConditionRuleInterface $conditionRule */
        foreach ($this->getConditionRules() as $conditionRule) {
            $conditionRule->modifyQuery($query);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultBuilderOptions(): array
    {
        return [
            'sortable' => false,
        ];
    }
}
