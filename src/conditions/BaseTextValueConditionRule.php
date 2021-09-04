<?php

namespace craft\conditions;

use Craft;
use craft\helpers\UrlHelper;

/**
 * The BaseTextValueConditionRule class provides a condition rule with a single input.
 *
 * @property-read array $inputAttributes
 * @property-read string $inputHtml
 * @property-read string $settingsHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseTextValueConditionRule extends BaseValueConditionRule
{
    /**
     * @inheritdoc
     */
    protected bool $showOperator = true;

    /**
     * @inheritdoc
     */
    public function getInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
            'inputAttributes' => $this->getInputAttributes(),
        ]);
    }

    /**
     * @return array
     */
    protected function getInputAttributes(): array
    {
        return [
            'hx-post' => UrlHelper::actionUrl('conditions/render'),
            'hx-trigger' => 'keyup changed delay:750ms',
            'name' => 'value',
            'value' => $this->value,
            'autocomplete' => false,
        ];
    }
}
