<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class HasDescendantsRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Has Descendants');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['hasDescendants'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->hasDescendants($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getCanonical()->getHasDescendants());
    }
}
