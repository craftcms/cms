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
abstract class BaseMultiSelectValueConditionRule extends BaseValueConditionRule
{
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
            'hx-trigger' => 'change changed delay:750ms',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplate('_includes/forms/multiselect', [
            'id' => 'author-groups',
            'class' => 'hidden selectize fullwidth',
            'name' => 'value',
            'values' => $this->value,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->getInputAttributes(),
        ]);

        $id = Craft::$app->getView()->namespaceInputId('author-groups');
        $js = <<<EOD
htmx.onLoad(function(content) {
 $('#$id').removeClass('hidden');
 $('#$id').selectize();
}
EOD;
        Craft::$app->getView()->registerJs($js);

        return $html;
    }
}
