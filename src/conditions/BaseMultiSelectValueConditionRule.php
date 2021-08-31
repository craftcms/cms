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
 * @since 4.0
 */
abstract class BaseMultiSelectValueConditionRule extends BaseValueConditionRule
{
    /**
     * The selectable options in the select input
     *
     * @return array
     * @since 4.0
     */
    abstract public function getSelectOptions(): array;

    /**
     * @return array
     * @since 4.0
     */
    protected function getInputAttributes(): array
    {
        return [
            'hx-post' => UrlHelper::actionUrl('conditions/render'),
            'hx-trigger' => 'change changed delay:750ms'
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplate('_includes/forms/multiselect', [
            'id' => 'author-groups',
            'name' => 'value',
            'values' => $this->value,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->getInputAttributes()
        ]);

        $js = <<<EOD
<script>
$('#author-groups').selectize({ placeholder: "Select a author group" });
</script>
EOD;
        $html .= $js;

        return $html;
    }
}