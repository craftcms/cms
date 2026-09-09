<?php

declare(strict_types=1);

namespace craft\base\conditions;

use CraftCms\Cms\Condition\ConditionRuleRenderer;
use CraftCms\Cms\Form\Form;
use CraftCms\Yii2Adapter\Form\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Condition\BaseLightswitchConditionRule instead. */
abstract class BaseLightswitchConditionRule extends \CraftCms\Cms\Condition\BaseLightswitchConditionRule
{
    public function getForm(): Form
    {
        return app(LegacyConditionRuleForm::class)->capture($this, $this->getHtml(...));
    }

    public function getHtml(): string
    {
        return app(LegacyConditionRuleForm::class)->render(Form::make($this->operatorNodes()), $this->inputHtml());
    }

    protected function inputHtml(): string
    {
        return app(ConditionRuleRenderer::class)->renderForm(Form::make($this->inputNodes()));
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();

        if (static::class === self::class) {
            $config['class'] = \CraftCms\Cms\Condition\BaseLightswitchConditionRule::class;
        }

        return $config;
    }
}
