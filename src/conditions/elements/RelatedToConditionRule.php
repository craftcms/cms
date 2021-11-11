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
 * @property int[] $elementIds
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class RelatedToConditionRule extends BaseConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return false;
    }

    /**
     * @var string
     */
    public string $elementType = Entry::class;

    /**
     * @var string[]|null
     */
    public ?array $sources = null;

    /**
     * @var array|null
     */
    public ?array $criteria = null;

    /**
     * @var array
     */
    private array $_elementIds = [];

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Related to');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    /**
     * @return int[]
     */
    public function getElementIds(): array
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
            'elementType' => $this->elementType,
            'elementIds' => $this->_elementIds,
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
            $this->elementSelectHtml(),
            [
                'class' => ['flex', 'flex-nowrap'],
            ]
        );
    }

    /**
     * Returns the element selector HTML.
     *
     * @return string
     */
    protected function elementSelectHtml(): string
    {
        return Cp::elementSelectHtml([
            'name' => 'elementIds',
            'elements' => $this->_elements(),
            'elementType' => $this->elementType,
            'sources' => $this->sources,
            'criteria' => $this->criteria,
            'single' => true,
        ]);
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
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['elementType', 'sources', 'criteria', 'elementIds'], 'safe'],
        ]);
    }
}
