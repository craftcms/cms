<?php

namespace craft\elements\conditions\assets;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use function CraftCms\Cms\t;

/**
 * Asset volume condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class VolumeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('Volume');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['volume', 'volumeId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        return Arr::pluck($volumes, 'name', 'uid');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $volumes = Craft::$app->getVolumes();
        $query->volumeId($this->paramValue(fn($uid) => $volumes->getVolumeByUid($uid)->id ?? null));
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->getVolume()->uid);
    }
}
