<?php

namespace craft\conditions;

use Craft;

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
     * @var string
     */
    protected string $_id = 'multi-select';

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
    protected function getInputAttributes(): array
    {
        return [
            'id' => $this->_id,
            'style' => 'display:none;' // Hide it before selectize does it's thing
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {

        $id = Craft::$app->getView()->namespaceInputId($this->_id);

        $html = Craft::$app->getView()->renderTemplate('_includes/forms/multiselect', [
            'class' => 'selectize fullwidth',
            'name' => 'optionValues',
            'values' => $this->_optionValues,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->getInputAttributes(),
        ]);

        $js = <<<JS
$('#$id').selectize({
    plugins: ["remove_button"],
    onDropdownClose: function(x) {
        htmx.trigger(htmx.find("#$id"), "change");
    }
});
JS;

        Craft::$app->getView()->registerJs($js);

        return $html;
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
