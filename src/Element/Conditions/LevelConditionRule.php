<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class LevelConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Level');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        ElementQuery::applyLevel($query, $this->paramValue(), $elementQuery);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->level ?? $element->getCanonical()->level);
    }
}
