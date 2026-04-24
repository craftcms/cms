<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class DateUpdatedConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Date Updated');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['dateUpdated'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->dateUpdated($this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->dateUpdated);
    }
}
