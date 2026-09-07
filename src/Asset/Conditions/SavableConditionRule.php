<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

class SavableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Savable');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        AssetQuery::applySavable($query, $this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return Gate::check('save', $element) === $this->value;
    }
}
