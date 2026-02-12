<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Conditions;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;

use function CraftCms\Cms\t;

final class VolumeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Volume');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['volume', 'volumeId'];
    }

    /**
     * {@inheritdoc}
     */
    protected function options(): array
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        return Arr::pluck($volumes, 'name', 'uid');
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $volumes = Craft::$app->getVolumes();

        $query->volumeId($this->paramValue(fn ($uid) => $volumes->getVolumeByUid($uid)->id ?? null));
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getVolume()->uid);
    }
}
