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
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseSelectOperatorConditionRule extends BaseOperatorConditionRule
{
    /**
     * Returns the selectable options in the select input.
     *
     * @return array
     */
    abstract public function getSelectOptions(): array;

    /**
     * Returns the input attributes.
     *
     * @return array
     */
    protected function getInputAttributes(): array
    {
        return [
            'hx-post' => UrlHelper::actionUrl('conditions/render'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(): string
    {
        $html = parent::getHtml();

        $html .= Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'value',
            'value' => $this->value,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->getInputAttributes(),
        ]);

        return $html;
    }
}
