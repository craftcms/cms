<?php

declare(strict_types=1);

namespace craft\elements\conditions\assets;

use CraftCms\Cms\Condition\ConditionRuleRenderer;
use CraftCms\Cms\Form\Form;
use CraftCms\Yii2Adapter\Form\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule instead. */
class DateModifiedConditionRule extends \CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule
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
            $config['class'] = \CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule::class;
        }

        return $config;
    }
}
