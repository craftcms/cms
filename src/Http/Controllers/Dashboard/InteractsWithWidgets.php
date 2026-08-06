<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;

trait InteractsWithWidgets
{
    protected function getWidgetIconSvg(WidgetInterface $widget): ?string
    {
        $icon = $widget->getIcon();
        $label = $widget->getDisplayName();

        return $icon ? Icons::svg($icon, $label) : Icons::fallbackSvg($label);
    }

    /**
     * @return array{id: int|null, type: string, colspan: int, title: string|null, subtitle: string|null, name: string, bodyHtml: string, settingsForm: FormPayload|null, settingsHtml: string|null, settingsJs: string|null, settings: array<string, mixed>}|false
     */
    protected function getWidgetInfo(WidgetInterface $widget): array|false
    {
        // Get the body HTML
        $widgetBodyHtml = $widget->getBodyHtml();

        if ($widgetBodyHtml === null) {
            return false;
        }

        $settings = $this->getWidgetSettingsInfo($widget, "widget{$widget->id}-settings");

        // Get the colspan (limited to the widget type's max allowed colspan)
        $colspan = $widget->colspan ?: 1;

        if (($maxColspan = $widget->getMaxColspan()) && $colspan > $maxColspan) {
            $colspan = $maxColspan;
        }

        return [
            'id' => $widget->id,
            'type' => $widget->getType(),
            'colspan' => $colspan,
            'title' => $widget->getTitle(),
            'subtitle' => $widget->getSubtitle(),
            'name' => $widget->getDisplayName(),
            'bodyHtml' => $widgetBodyHtml,
            'settings' => $widget->getSettings(),
            ...$settings,
        ];
    }

    /** @return array{settingsForm: FormPayload|null, settingsHtml: string|null, settingsJs: string|null} */
    protected function getWidgetSettingsInfo(WidgetInterface $widget, string $namespace): array
    {
        $context = new FormContext(
            namespace: $namespace,
            values: [$namespace => $widget->getSettings()],
            errors: $widget->errors()->getMessages(),
            refreshable: true,
        );
        $form = $widget->settingsForm($context);

        return [
            'settingsForm' => $form === null ? null : app(FormResolver::class)->resolve($form, $context),
            'settingsHtml' => null,
            'settingsJs' => null,
        ];
    }
}
