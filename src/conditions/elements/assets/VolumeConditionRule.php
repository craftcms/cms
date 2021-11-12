<?php

namespace craft\conditions\elements\assets;

use Craft;
use craft\conditions\BaseSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\AssetQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Asset volume condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class VolumeConditionRule extends BaseSelectConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Volume');
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
        return ArrayHelper::map($volumes, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $volume = Craft::$app->getVolumes()->getVolumeByUid($this->value);

        if ($volume) {
            /** @var AssetQuery $query */
            $query->volume($volume);
        }
    }
}
