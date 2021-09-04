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
 * @property-read \craft\elements\db\ElementQuery $elementQuery
 * @property Collection $conditionRules
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class ElementQueryCondition extends BaseCondition implements ElementQueryConditionInterface
{
    /**
     * @inheritdoc
     */
    abstract public function getElementQuery(): ElementQueryInterface;

    private $_showDebug = false;

    /**
     * @inheritdoc
     */
    public function getAddRuleLabel(): string
    {
        return Craft::t('app', 'Add a filter');
    }

    /**
     * @inheritdoc
     */
    protected function validateConditionRule($rule): bool
    {
        return $rule instanceof ElementQueryConditionRuleInterface;
    }

    /**
     * @inheritdoc
     */
    public function getQuery(): QueryInterface
    {
        /** @var ElementQuery $query */
        $query = $this->getElementQuery();
        return $this->modifyQuery($query);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getBuilderHtml(): string
    {
        if ($this->_showDebug) {
            $titles = '';
            foreach ($this->getQuery()->all() as $entry) {
                $titles .= $entry->title . '<br>';
            }
            $html = parent::getBuilderHtml();
            $html .= Html::tag('div',
                Html::tag('h3', 'Element Query') .
                Html::tag('pre', Json::encode($this->getQuery(), JSON_PRETTY_PRINT)),
                ['class' => 'pane']);
            $html .= Html::tag('div', Html::tag('h3', 'Results') . $titles, ['class' => 'pane']);
            return $html;
        }

        return parent::getBuilderHtml();
    }
}
