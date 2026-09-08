<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class DateCreatedConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Date Created');
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        ElementQuery::applyDateCreated($query, $this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->dateCreated);
    }
}
