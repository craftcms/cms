<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\LegacyHtml;

/** @phpstan-require-implements ConfigurableComponentInterface */
trait LegacySettingsForm
{
    public function settingsForm(FormContext $context = new FormContext()): ?Form
    {
        $node = app(LegacyHtml::class)->settings(
            component: $this,
            path: '__legacySettings',
            namespace: LegacyHtml::namespace($context->namespace),
            mode: match ($context->mode) {
                ControlMode::Editable => LegacyHtmlMode::Editable,
                ControlMode::ReadOnly => LegacyHtmlMode::ReadOnly,
                ControlMode::Disabled => LegacyHtmlMode::Disabled,
            },
        );

        $node?->getControl()
            ->deltaGroupAtNamespace()
            ->expandValues();

        return $node === null ? null : Form::make([$node]);
    }
}
