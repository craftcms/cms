<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;

/**
 * BaseLightswitchConditionRule provides a base implementation for condition rules that are composed of a lightswitch input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
abstract class BaseLightswitchConditionRule extends BaseConditionRule
{
    public bool $value = true;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function inputHtml(): string
    {
        $lightswitchId = 'lightswitch';
        $labelId = "$lightswitchId-label";

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $lightswitchId, [
                'id' => $labelId,
            ]).
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
     * {@inheritdoc}
     */
    #[\Override]
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'safe'],
        ]);
    }

    /**
     * Returns whether the condition rule matches the given value.
     */
    protected function matchValue(bool $value): bool
    {
        return $this->value === $value;
    }
}
