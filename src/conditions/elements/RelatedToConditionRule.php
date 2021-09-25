<?php

namespace craft\conditions\elements;

use Craft;
use craft\base\BlockElementInterface;
use craft\base\ElementInterface;
use craft\conditions\BaseConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use yii\db\QueryInterface;

/**
 * Relation condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelatedToConditionRule extends BaseConditionRule implements QueryConditionRuleInterface
{
    /**
     * @var string
     */
    public string $elementType = Entry::class;

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
        return [];
    }

    /**
     * @return array
     */
    public function getElementIds()
    {
        return $this->_elementIds;
    }

    /**
     * @param string|int|int[] $value
     */
    public function setElementIds($value): void
    {
        if ($value === '') {
            $this->_elementIds = [];
        } else {
            $this->_elementIds = array_map(fn($id) => (int)$id, ArrayHelper::toArray($value));
        }
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        if (!empty($this->_elementIds)) {
            /** @var ElementQuery $query */
            $query->andRelatedTo($this->_elementIds);
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementIds' => $this->_elementIds
        ]);
    }

    /**
     * @inheritdochandleException
     */
    public function getHtml(array $options = []): string
    {
        return Html::tag('div',
            Cp::selectHtml([
                'name' => 'elementType',
                'options' => $this->_elementTypeOptions(),
                'value' => $this->elementType,
                'inputAttributes' => [
                    'hx' => [
                        'post' => UrlHelper::actionUrl('conditions/render'),
                    ],
                ],
            ]) .
            Cp::elementSelectHtml([
                'name' => 'elementIds',
                'elements' => $this->_elements(),
                'elementType' => $this->elementType,
                'single' => true,
            ]),
            [
                'class' => ['flex', 'flex-nowrap'],
            ]
        );
    }

    /**
     * @return array
     */
    private function _elementTypeOptions(): array
    {
        $options = [];
        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            /** @var string|ElementInterface $elementType */
            if (!is_subclass_of($elementType, BlockElementInterface::class)) {
                $options[] = [
                    'value' => $elementType,
                    'label' => $elementType::displayName(),
                ];
            }
        }
        return $options;
    }

    /**
     * @return ElementInterface[]
     */
    private function _elements(): array
    {
        if (empty($this->_elementIds)) {
            return [];
        }

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        return $elementType::find()
            ->id($this->_elementIds)
            ->status(null)
            ->all();
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['elementType', 'elementIds'], 'safe'],
        ]);
    }
}
