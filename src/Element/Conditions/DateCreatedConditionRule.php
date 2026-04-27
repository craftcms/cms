<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class DateCreatedConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Date Created');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['dateCreated'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->dateCreated($this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->dateCreated);
    }
}
