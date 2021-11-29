<?php

namespace craft\conditions;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * BaseMultiSelectConditionRule provides a base implementation for condition rules that are composed of a multi-select input.
 *
 * @property string[] $values
 * @since 4.0.0
 */
abstract class BaseMultiSelectConditionRule extends BaseConditionRule
{
    /**
     * @var string[]
     */
    private array $_values = [];

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->_values;
    }

    /**
     * @param string|string[] $values
     */
    public function setValues($values): void
    {
        if ($values === '') {
            $this->_values = [];
        } else {
            $this->_values = ArrayHelper::toArray($values);
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'values' => $this->_values,
        ]);
    }

    /**
     * Defines the selectable options.
     *
     * Options can be expressed as value/label pairs, or as arrays with `value` and `label` keys.
     *
     * @return string[]
     */
    abstract protected function options(): array;

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $multiSelectId = 'multiselect';
        $namespacedId = Craft::$app->getView()->namespaceInputId($multiSelectId);

        $js = <<<JS
$('#$namespacedId').selectize({
    plugins: ['remove_button'],
    onDropdownClose: () => {
        htmx.trigger(htmx.find('#$namespacedId'), 'change');
    },
});
JS;
        Craft::$app->getView()->registerJs($js);

        return
            Html::hiddenLabel($this->getLabel(), $multiSelectId) .
            Cp::multiSelectHtml([
                'id' => $multiSelectId,
                'class' => 'selectize fullwidth',
                'name' => 'values',
                'values' => $this->_values,
                'options' => $this->options(),
                'inputAttributes' => [
                    'style' => [
                        'display' => 'none', // Hide it before selectize does its thing
                    ],
                ],
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['values'], 'safe'],
        ]);
    }
}
