<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Form\Concerns\LegacySettingsForm;

trait LegacyBuiltInField
{
    use LegacyFieldControl {
        formControl as private legacyFormControl;
    }
    use LegacyFieldHtml {
        getStaticHtml as private legacyStaticHtml;
    }
    use LegacySettingsForm {
        settingsForm as private legacySettingsForm;
    }

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
            return $this->legacyFormControl($context);
        }

        return parent::formControl($context);
    }

    public function getSettingsHtml(): ?string
    {
        $form = parent::settingsForm();
        if ($form === null) {
            return null;
        }

        $payload = app(FormResolver::class)->resolve($form, new FormContext());

        return app(FormHtmlRenderer::class)->render($payload);
    }

    public function getReadOnlySettingsHtml(): ?string
    {
        return Html::disableInputs(fn() => $this->getSettingsHtml());
    }

    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        if (static::class !== self::class) {
            return $this->legacyStaticHtml($value, $element);
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
