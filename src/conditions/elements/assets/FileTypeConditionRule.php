<?php

namespace craft\conditions\elements\assets;

use Craft;
use craft\conditions\BaseMultiSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\AssetQuery;
use craft\helpers\Assets as AssetsHelper;
use yii\db\QueryInterface;

/**
 * Asset volume condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FileTypeConditionRule extends BaseMultiSelectConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'File Type');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['kind'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $options = [];
        foreach (AssetsHelper::getAllowedFileKinds() as $value => $kind) {
            $fileKindOptions[] = ['value' => $value, 'label' => $kind['label']];
        }
        return $options;
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->kind($this->values);
    }
}
