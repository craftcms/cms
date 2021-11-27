<?php

namespace craft\conditions\elements\assets;

use Craft;
use craft\conditions\BaseElementSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\AssetQuery;
use craft\elements\User;
use yii\db\QueryInterface;

/**
 * Uploader condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class UploaderConditionRule extends BaseElementSelectConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Uploaded By');
    }

    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        return User::class;
    }

    /**
     * @inheritdoc
     */
    protected function criteria(): ?array
    {
        return [
            'assetUploaders' => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['uploader', 'uploaderId'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->uploader($this->getElementId());
    }
}
