<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class HasUrlConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Has URL');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['uri'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        if ($this->value) {
            $query->uri('not :empty:');
        } else {
            $query->uri(':empty:');
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getUrl() !== null);
    }
}
