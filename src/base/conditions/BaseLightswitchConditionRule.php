<?php

namespace craft\base\conditions;

use craft\helpers\Cp;
use craft\helpers\Html;

/**
 * BaseLightswitchConditionRule provides a base implementation for condition rules that are composed of a lightswitch input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseLightswitchConditionRule extends BaseConditionRule
{
    /**
     * @var bool
     */
    public bool $value = true;

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
    protected function inputHtml(): string
    {
        $lightswitchId = 'lightswitch';
        $labelId = "$lightswitchId-label";

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $lightswitchId, [
                'id' => $labelId,
            ]) .
            Html::tag('div',
                Cp::lightswitchHtml([
                    'id' => $lightswitchId,
                    'on' => $this->value,
                    'name' => 'value',
                    'labelledBy' => $labelId,
                ])
            );
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

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param bool $value
     * @return bool
     */
    protected function matchValue(bool $value): bool
    {
        return $this->value === $value;
    }
}
