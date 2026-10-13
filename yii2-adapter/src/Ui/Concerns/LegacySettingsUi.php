<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Ui\Concerns;

use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Yii2Adapter\Ui\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Ui\LegacyHtml;

/** @phpstan-require-implements ConfigurableComponentInterface */
trait LegacySettingsUi
{
    public function settingsUi(UiContext $context = new UiContext()): ?Ui
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

        return $node === null ? null : Ui::make([$node]);
    }
}
