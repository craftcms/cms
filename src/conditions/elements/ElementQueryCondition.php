<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseCondition;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use Illuminate\Support\Collection;
use yii\base\InvalidConfigException;
use yii\db\QueryInterface;

/**
 * Base class for conditions designed for element queries.
 *
 * @property-read string $addRuleLabel
 * @property-read string $elementType
 * @property-read QueryInterface $query
 * @property-read string $html
 * @property Collection $conditionRules
 */
abstract class ElementQueryCondition extends BaseCondition implements ElementQueryConditionInterface
{
    abstract public function getElementType(): string;

    /**
     * @inheritDoc
     */
    public function getAddRuleLabel(): string
    {
        return Craft::t('app', 'Add Condition');
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): QueryInterface
    {
        /** @var ElementQuery $query */
        $query = $this->getElementType()::find();
        return $this->modifyQuery($query);
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        foreach ($this->getConditionRules() as $conditionRule) {
            if ($conditionRule instanceof ElementQueryConditionRuleInterface) {
                $query = $conditionRule->modifyQuery($query);
            } else {
                throw new InvalidConfigException("Using a condition rule class " . get_class($conditionRule) . ", that is not compatible with element queries");
            }
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getHtml(): string
    {
        $titles = '';
        foreach ($entries = $this->getQuery()->all() as $entry){
         $titles .= $entry->title. '<br>';
        }
        $html = parent::getHtml();
        $html .= Html::tag('div',
            Html::tag('h3','Element Query') .
            Html::tag('pre', Json::encode($this->getQuery(), JSON_PRETTY_PRINT)),
            ['class' => 'pane']);
        $html .= Html::tag('div', Html::tag('h3','Results') . $titles, ['class' => 'pane']);
        return $html;
    }
}