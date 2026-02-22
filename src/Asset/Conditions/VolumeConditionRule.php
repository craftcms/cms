<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Volumes;

use function CraftCms\Cms\t;

final class VolumeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Volume');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['volume', 'volumeId'];
    }

    protected function options(): array
    {
        return Volumes::getAllVolumes()->pluck('name', 'uid')->all();
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->volumeId($this->paramValue(fn ($uid) => Volumes::getVolumeByUid($uid)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getVolume()->uid);
    }
}
