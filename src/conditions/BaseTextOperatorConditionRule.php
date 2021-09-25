<?php

namespace craft\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

/**
 * The BaseTextOperatorConditionRule class provides a condition rule with a single input with operator.
 *
 * @property-read array $inputAttributes
 * @property-read string $inputHtml
 * @property-read string $settingsHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseTextOperatorConditionRule extends BaseOperatorConditionRule
{
    /**
     * @var string
     */
    public string $value = '';

    /**
     * @inheritdoc
     */
    protected bool $showOperator = true;

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getHtml(array $options = []): string
    {
        return
            Html::beginTag('div', [
                'class' => ['flex', 'flex-nowrap'],
            ]) .
            parent::getHtml($options) .
            Html::tag('div',
                Cp::textHtml([
                    'name' => 'value',
                    'value' => $this->value,
                    'autocomplete' => false,
                    'inputAttributes' => [
                        'hx' => [
                            'post' => UrlHelper::actionUrl('conditions/render'),
                            'trigger' => 'keyup changed delay:750ms',
                        ],
                    ]
                ])
            ) .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'safe'],
        ]);
    }
}
