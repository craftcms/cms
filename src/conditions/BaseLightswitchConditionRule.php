<?php

namespace craft\conditions;

use Craft;
use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * The BaseTextOperatorConditionRule class provides a condition rule with a single input with operator.
 *
 * @property-read array $inputAttributes
 * @property-read string $inputHtml
 * @property-read string $settingsHtml
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseLightswitchConditionRule extends BaseConditionRule
{
    /**
     * @var bool
     */
    public bool $value = false;

    /**
     * Returns the input container attributes.
     *
     * @return array
     */
    protected function containerAttributes(): array
    {
        return [];
    }

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
        $html = Html::beginTag('div', ['class' => ['flex', 'flex-nowrap']]);
        $html .= Html::tag('div',
            Cp::lightswitchHtml([
                'small' => true,
                'on' => $this->value,
                'name' => 'value',
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
