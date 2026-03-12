<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseNumberConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class HeightConditionRule extends BaseNumberConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Height');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['height'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->height($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getHeight());
    }
}
