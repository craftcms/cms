<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table as TableField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;

trait LegacyBuiltInField
{
    private ControlMode $tableSettingsMode = ControlMode::Editable;

    use LegacyFieldControl {
        formControl as private legacyFormControl;
    }
    use LegacyFieldHtml {
        getStaticHtml as private legacyStaticHtml;
    }
    use LegacySettingsForm {
        settingsForm as private legacySettingsForm;
    }

    private ControlMode $legacyInputMode = ControlMode::Editable;

    public function settingsForm(FormContext $context = new FormContext()): Form
    {
        if (static::class !== self::class) {
            return $this->legacySettingsForm($context) ?? Form::make();
        }

        return parent::settingsForm($context);
    }

    public function formControl(FieldContext $context): Control
    {
        if (static::class !== self::class) {
            $previousMode = $this->legacyInputMode;
            $this->legacyInputMode = $context->form->mode === ControlMode::Editable ? $context->mode : $context->form->mode;

            try {
                return $this->legacyFormControl($context);
            } finally {
                $this->legacyInputMode = $previousMode;
            }
        }

        return parent::formControl($context);
    }

    public function getSettingsHtml(): ?string
    {
        if ($this instanceof TableField) {
            return $this->tableSettingsHtml($this->tableSettingsMode);
        }

        $form = parent::settingsForm();
        if ($form === null) {
            return null;
        }

        $payload = app(FormResolver::class)->resolve($form, new FormContext());

        return app(FormHtmlRenderer::class)->render($payload);
    }

    public function getReadOnlySettingsHtml(): ?string
    {
        if (!$this instanceof TableField) {
            return Html::disableInputs(fn() => $this->getSettingsHtml());
        }

        $previousMode = $this->tableSettingsMode;
        $this->tableSettingsMode = ControlMode::ReadOnly;

        try {
            return Html::disableInputs(fn() => $this->getSettingsHtml());
        } finally {
            $this->tableSettingsMode = $previousMode;
        }
    }

    private function tableSettingsHtml(ControlMode $mode): string
    {
        $context = new FormContext(namespace: 'settings', mode: $mode, refreshable: $mode === ControlMode::Editable);
        $payload = app(FormResolver::class)->resolve(parent::settingsForm($context), $context);

        return Html::tag('craft-field-settings-form', '', [
            'name' => '__fieldSettings',
            'data-payload' => Json::encode($payload),
            'data-field-type' => TableField::class,
            'data-field-id' => $this->id,
        ]);
    }

    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        if (static::class !== self::class) {
            $previousMode = $this->legacyInputMode;
            $this->legacyInputMode = ControlMode::ReadOnly;

            try {
                return $this->legacyStaticHtml($value, $element);
            } finally {
                $this->legacyInputMode = $previousMode;
            }
        }

        $context = new FormContext(mode: ControlMode::ReadOnly);
        $control = parent::formControl(new FieldContext(
            path: $this->handle,
            value: $value,
            element: $element,
            form: $context,
            mode: ControlMode::ReadOnly,
        ));
        $payload = app(FormResolver::class)->resolve(
            Form::make([Field::make()->control($control)]),
            $context,
        );

        return app(FormHtmlRenderer::class)->render($payload);
    }
}
