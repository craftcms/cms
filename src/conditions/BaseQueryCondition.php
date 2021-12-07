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
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule, array $options): bool
    {
        if (!$rule instanceof QueryConditionRuleInterface) {
            return false;
        }

        if (!parent::isConditionRuleSelectable($rule, $options)) {
            return false;
        }

        // Make sure the rule doesn't conflict with the existing params
        $queryParams = $options['queryParams'] ?? [];
        foreach ($this->getConditionRules() as $existingRule) {
            /** @var QueryConditionRuleInterface $existingRule */
            array_push($queryParams, ...$existingRule->getExclusiveQueryParams());
        }

        $queryParams = array_flip($queryParams);

        foreach ($rule->getExclusiveQueryParams() as $param) {
            if (isset($queryParams[$param])) {
                return false;
            }
        }

        return true;
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

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        foreach ($this->getConditionRules() as $conditionRule) {
            $conditionRule->modifyQuery($query);
        }
    }
}
