<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

final class ViewableConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Viewable');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['editable'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->editable($this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        $viewable = Craft::$app->getElements()->canView($element);

        return $viewable === $this->value;
    }
}
