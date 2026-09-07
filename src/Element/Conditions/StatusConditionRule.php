<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class StatusConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        // Exclude from element query conditions
        if ($condition instanceof ElementCondition && $condition->forQuery) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Status');
    }

    protected function options(): array
    {
        /** @var ElementCondition $condition */
        $condition = $this->getCondition();

        return array_map(fn ($info) => $info['label'] ?? $info, $condition->elementType::statuses());
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        ElementQuery::applyStatus($query, $this->paramValue(), $elementQuery);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getStatus());
    }
}
