<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class HasAltConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Has alternative text');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['hasAlt'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->hasAlt($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->alt !== null);
    }
}
