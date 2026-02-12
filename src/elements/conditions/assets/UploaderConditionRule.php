<?php

namespace craft\elements\conditions\assets;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;

/**
 * Uploader condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class UploaderConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
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
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->uploader($this->getElementId());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Asset $element */
        return $this->matchValue($element->uploaderId);
    }
}
