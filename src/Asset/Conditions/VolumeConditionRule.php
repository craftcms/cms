<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Database\Query\Builder;

use function CraftCms\Cms\t;

class VolumeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof AssetCondition) {
            return false;
        }

        // Exclude from volume sources
        if (isset($condition->sourceKey) && str_starts_with($condition->sourceKey, 'volume:')) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Volume');
    }

    protected function options(): array
    {
        return Volumes::getAllVolumes()->pluck('name', 'uid')->all();
    }

    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void
    {
        AssetQuery::applyVolumeId($query, $this->paramValue(fn ($uid) => Volumes::getVolumeByUid($uid)->id ?? null));
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getVolume()->uid);
    }
}
