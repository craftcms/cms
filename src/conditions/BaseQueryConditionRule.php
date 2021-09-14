<?php

namespace craft\conditions;

use yii\db\QueryInterface;

/**
 * BaseQueryCondition defines base query condition to be extended by query condition classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseQueryConditionRule extends BaseConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    abstract public function modifyQuery(QueryInterface $query): void;

    /**
     * @inheritdoc
     */
    public function validateCondition(ConditionInterface $condition): bool
    {
        return parent::validateCondition($condition) && $condition instanceof QueryConditionInterface;
    }
}
