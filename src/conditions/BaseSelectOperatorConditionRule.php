<?php

namespace craft\conditions;

use Craft;

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
     * @var string
     */
    public string $optionValue = '';

    /**
     * Returns the selectable options in the select input.
     *
     * @return array
     */
    abstract public function getSelectOptions(): array;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'optionValue' => $this->optionValue,
        ]);
    }

    /**
     * Returns the input attributes.
     *
     * @return array
     */
    protected function inputAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        $html = parent::getHtml($options);

        $html .= Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'name' => 'optionValue',
            'value' => $this->optionValue,
            'options' => $this->getSelectOptions(),
            'inputAttributes' => $this->inputAttributes(),
        ]);

        return $html;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['optionValue'], 'safe'],
        ]);
    }
}
