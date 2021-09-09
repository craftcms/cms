<?php

namespace craft\conditions;

use Craft;
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
    public function getHtml(): string
    {
        $html = Html::beginTag('div', ['class' => ['flex', 'flex-nowrap']]);
        $html .= parent::getHtml();
        $html .= Html::tag('div',
            Craft::$app->getView()->renderTemplate('_includes/forms/text', [
                'inputAttributes' => [
                    'hx-post' => UrlHelper::actionUrl('conditions/render'),
                    'hx-trigger' => 'keyup changed delay:750ms',
                    'name' => 'value',
                    'value' => $this->value,
                    'autocomplete' => false,
                ]
            ])
        );
        $html .= Html::endTag('div');

        return $html;
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
