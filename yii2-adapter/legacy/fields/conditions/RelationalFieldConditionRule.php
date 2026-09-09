<?php

declare(strict_types=1);

namespace craft\fields\conditions;

use CraftCms\Cms\Form\Form;
use CraftCms\Yii2Adapter\Form\LegacyConditionRuleForm;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule instead. */
class RelationalFieldConditionRule extends \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule
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
        return app(LegacyConditionRuleForm::class)->elementInput($this, Form::make($this->inputNodes()), $this->elementSelectConfig());
    }

    /** @return array<string, mixed> */
    protected function elementSelectConfig(): array
    {
        return app(LegacyConditionRuleForm::class)->elementOptions($this->elementSelect());
    }

    public function getConfig(): array
    {
        $config = parent::getConfig();

        if (static::class === self::class) {
            $config['class'] = \CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule::class;
        }

        return $config;
    }
}
