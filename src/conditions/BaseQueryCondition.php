<?php

namespace craft\conditions;

use yii\db\QueryInterface;

/**
 * BaseQueryCondition defines base query condition to be extended by query condition classes.
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
    public function validateConditionRule(ConditionRuleInterface $rule): bool
    {
        return parent::validateConditionRule($rule) && $rule instanceof QueryConditionRuleInterface;
    }
}
