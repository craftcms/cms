<?php

namespace craft\conditions;

use Craft;
use craft\helpers\Cp;

/**
 * The BaseSelectValueConditionRule class provides a condition rule with a single select box.
 *
 * @property array $optionValues
 * @property-read string[] $selectOptions
 * @property-read array $inputAttributes
 * @property-read string $inputHtml
 * @property-read string $settingsHtml
 *
 * @since 4.0.0
 */
abstract class BaseMultiSelectOperatorConditionRule extends BaseConditionRule
{
    /**
     * @var array
     */
    private array $_optionValues = [];

    /**
     * @return array
     */
    public function getOptionValues(): array
    {
        return $this->_optionValues;
    }

    /**
     * @param array $values
     */
    public function setOptionValues(array $values): void
    {
        $this->_optionValues = $values;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'optionValues' => $this->_optionValues,
        ]);
    }

    /**
     * The selectable options in the select input
     *
     * @return array
     */
    abstract public function getSelectOptions(): array;

    /**
     * @return array
     */
    protected function inputAttributes(): array
    {
        return [
            'style' => [
                'display' => 'none', // Hide it before selectize does its thing
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $id = 'multiselect' . mt_rand();
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        $js = <<<JS
$('#$namespacedId').selectize({
    plugins: ["remove_button"],
    onDropdownClose: function(x) {
        htmx.trigger(htmx.find("#$namespacedId"), "change");
    }
});
JS;
        Craft::$app->getView()->registerJs($js);

        return Cp::multiSelectHtml([
            'id' => $id,
            'class' => 'selectize fullwidth',
            'name' => 'optionValues',
            'values' => $this->_optionValues,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->inputAttributes(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['optionValues'], 'safe'],
        ]);
    }
}
