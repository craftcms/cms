<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class StatusConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Status');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['status'];
    }

    protected function options(): array
    {
        /** @var ElementCondition $condition */
        $condition = $this->getCondition();

        return array_map(fn ($info) => $info['label'] ?? $info, $condition->elementType::statuses());
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->status($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getStatus());
    }
}
