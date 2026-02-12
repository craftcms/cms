<?php

declare(strict_types=1);

namespace CraftCms\Cms\Condition;

use craft\helpers\Cp;
use CraftCms\Cms\Support\Html;
use Override;

/**
 * BaseLightswitchConditionRule provides a base implementation for condition rules that are composed of a lightswitch input.
 */
abstract class BaseLightswitchConditionRule extends BaseConditionRule
{
    public bool $value = true;

    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    #[Override]
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

    #[\Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'value' => ['boolean'],
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
