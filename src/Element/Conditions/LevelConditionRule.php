<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class LevelConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Level');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['level'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->level($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->level ?? $element->getCanonical()->level);
    }
}
