<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use yii\db\QueryInterface;

/**
 * Element trashed condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelatedToConditionRule extends BaseConditionRule implements QueryConditionRuleInterface
{
    /**
     * @var array
     */
    private array $_elementIds = [];

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Related to');
    }

    /**
     * @inheritdoc
     */
    public static function queryParams(): array
    {
        return ['relatedTo'];
    }

    /**
     * @param $value
     */
    public function setElementIds($value): void
    {
        if (is_string($value) && !empty($value)) {
            $value = [(int)$value];
        }

        if (!is_array($value)) {
            $value = [];
        }

        $this->_elementIds = $value;
    }

    public function getElementIds()
    {
        return $this->_elementIds;
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        if (count($this->elementIds) > 0) {
            /** @var ElementQuery $query */
            $query->relatedTo($this->elementIds);
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementIds' => $this->elementIds
        ]);
    }

    /**
     * @inheritdochandleException
     */
    public function getHtml(array $options = []): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/elementSelect', [
            'name' => 'elementIds',
            'elements' => $this->elementIds ? Entry::find()->id($this->elementIds)->all() : [],
            'elementType' => Entry::class,
            'single' => true
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['elementIds'], 'safe'],
        ]);
    }
}
