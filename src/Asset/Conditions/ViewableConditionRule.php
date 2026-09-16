<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

class ViewableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
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
        return t('Viewable');
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        AssetQuery::applyEditable($query, $this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return Gate::check('view', $element) === $this->value;
    }
}
