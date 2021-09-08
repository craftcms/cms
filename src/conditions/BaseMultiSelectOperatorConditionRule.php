<?php

namespace craft\conditions;

use Craft;
use craft\helpers\UrlHelper;

/**
 * The BaseSelectValueConditionRule class provides a condition rule with a single select box.
 *
 * @property-read string[] $selectOptions
 * @property-read array $inputAttributes
 * @property-read string $inputHtml
 * @property-read string $settingsHtml
 *
 * @since 4.0.0
 */
abstract class BaseMultiSelectOperatorConditionRule extends BaseConditionRule
{
    protected string $_id = 'multi-select';

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
            'hx-post' => UrlHelper::actionUrl('conditions/render'),
            'hx-trigger' => 'change changed',
            'id' => $this->_id
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(): string
    {

        $id = Craft::$app->getView()->namespaceInputId($this->_id);

        $html = Craft::$app->getView()->renderTemplate('_includes/forms/multiselect', [
            'class' => 'selectize',
            'name' => 'value',
            'values' => $this->authorGroups,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->getInputAttributes(),
        ]);

        $js = <<<EOD
$('#$id').removeClass('hidden');

$('#$id').selectize({
    plugins: ["remove_button"],
    onChange: function(e){
       document.querySelector('#$id').dispatchEvent(new Event("change"));
    }
});
EOD;

        Craft::$app->getView()->registerJs($js);

        return $html;
    }
}
