<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class DateModifiedConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof AssetCondition) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('File Modification Date');
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        AssetQuery::applyDateModified($query, $this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->dateModified);
    }
}
