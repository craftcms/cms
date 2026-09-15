<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Support\Html;

/** @phpstan-require-extends \CraftCms\Cms\Condition\BaseLightswitchConditionRule */
trait LegacyLightswitchConditionRule
{
    use LegacyConditionRuleForm;

    protected function inputHtml(): string
    {
        $lightswitchId = 'lightswitch';
        $labelId = "$lightswitchId-label";

        return
            Html::hiddenLabel(Html::encode($this->getLabel()), $lightswitchId, [
                'id' => $labelId,
            ]) .
            Html::tag('div',
                Lightswitch::make()
                    ->id($lightswitchId)
                    ->on($this->value)
                    ->name('value')
                    ->labelledBy($labelId)
                    ->toHtml()
            );
    }
}
