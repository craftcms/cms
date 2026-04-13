<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class HasDescendantsRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Has Descendants');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['hasDescendants'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->hasDescendants($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getCanonical()->getHasDescendants());
    }
}
