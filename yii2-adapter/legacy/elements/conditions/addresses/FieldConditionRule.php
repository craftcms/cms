<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Cms\Condition\ConditionRuleRenderer;
use CraftCms\Cms\Form\Form;
use CraftCms\Yii2Adapter\Form\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\FieldConditionRule instead. */
class FieldConditionRule extends \CraftCms\Cms\Address\Conditions\FieldConditionRule
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
            $config['class'] = \CraftCms\Cms\Address\Conditions\FieldConditionRule::class;
        }

        return $config;
    }
}
