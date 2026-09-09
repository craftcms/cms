<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseConditionGroup;
use CraftCms\Cms\Condition\Enums\GroupOperator;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * @property Collection<int, self|ElementConditionRuleInterface|ElementQueryConditionRuleInterface> $rules
 */
class ElementConditionGroup extends BaseConditionGroup
{
    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        $method = $this->operator === GroupOperator::And ? 'where' : 'orWhere';

        foreach ($this->getRules() as $rule) {
            $query->$method(function (Builder $query) use ($elementQuery, $rule) {
                try {
                    /** @var self|ElementQueryConditionRuleInterface $rule */
                    $rule->modifyQuery($query, $elementQuery);
                } catch (RuntimeException) {
                    // The rule is misconfigured
                }
            });
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        $method = $this->operator === GroupOperator::And ? 'array_all' : 'array_any';

        return $method($this->getRules(), fn (self|ElementConditionRuleInterface $rule) => $rule->matchElement($element));
    }
}
