<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Cms\Form\Form;
use CraftCms\Yii2Adapter\Form\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule instead. */
class DependentLocalityConditionRule extends \CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule
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
        return app(LegacyConditionRuleForm::class)->textInput($this, Form::make($this->inputNodes()), $this->inputOptions());
    }

    /** @return array<string, mixed> */
    protected function inputOptions(): array
    {
        return app(LegacyConditionRuleForm::class)->textOptions($this, $this->inputType());
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();

        if (static::class === self::class) {
            $config['class'] = \CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule::class;
        }

        return $config;
    }
}
