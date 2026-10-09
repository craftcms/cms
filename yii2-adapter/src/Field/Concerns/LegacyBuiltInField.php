<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Field\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Table as TableField;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Yii2Adapter\Ui\Concerns\LegacySettingsUi;

trait LegacyBuiltInField
{
    private ControlMode $tableSettingsMode = ControlMode::Editable;

    use LegacyFieldControl {
        uiControl as private legacyUiControl;
    }
    use LegacyFieldHtml {
        getStaticHtml as private legacyStaticHtml;
    }
    use LegacySettingsUi {
        settingsUi as private legacySettingsUi;
    }

    private ControlMode $legacyInputMode = ControlMode::Editable;

    public function settingsUi(UiContext $context = new UiContext()): Ui
    {
        if (static::class !== self::class) {
            return $this->legacySettingsUi($context) ?? Ui::make();
        }

        return parent::settingsUi($context);
    }

    public function uiControl(FieldContext $context): Control
    {
        if (static::class !== self::class) {
            $previousMode = $this->legacyInputMode;
            $this->legacyInputMode = $context->ui->mode === ControlMode::Editable ? $context->mode : $context->ui->mode;

            try {
                return $this->legacyUiControl($context);
            } finally {
                $this->legacyInputMode = $previousMode;
            }
        }

        return parent::uiControl($context);
    }

    public function getSettingsHtml(): ?string
    {
        if ($this instanceof TableField) {
            return $this->tableSettingsHtml($this->tableSettingsMode);
        }

        $ui = parent::settingsUi();
        if ($ui === null) {
            return null;
        }

        $payload = app(UiResolver::class)->resolve($ui, new UiContext());

        return app(UiHtmlRenderer::class)->render($payload);
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
        $context = new UiContext(namespace: 'settings', mode: $mode, refreshable: $mode === ControlMode::Editable);
        $payload = app(UiResolver::class)->resolve(parent::settingsUi($context), $context);

        return Html::tag('craft-field-settings-ui', '', [
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

        $context = new UiContext(mode: ControlMode::ReadOnly);
        $control = parent::uiControl(new FieldContext(
            path: $this->handle,
            value: $value,
            element: $element,
            ui: $context,
            mode: ControlMode::ReadOnly,
        ));
        $payload = app(UiResolver::class)->resolve(
            Ui::make([Field::make()->control($control)]),
            $context,
        );

        return app(UiHtmlRenderer::class)->render($payload);
    }
}
