<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;

trait InteractsWithWidgets
{
    protected function getWidgetIconSvg(WidgetInterface $widget): ?string
    {
        $icon = $widget->getIcon();
        $label = $widget->getDisplayName();

        return $icon ? Icons::svg($icon, $label) : Icons::fallbackSvg($label);
    }

    /**
     * @return array{id: int|null, type: string, colspan: int, title: string|null, subtitle: string|null, name: string, bodyHtml: string, settingsHtml: string, settingsJs: string, settings: array<string, mixed>}|false
     */
    protected function getWidgetInfo(WidgetInterface $widget): array|false
    {
        // Get the body HTML
        $widgetBodyHtml = $widget->getBodyHtml();

        if ($widgetBodyHtml === null) {
            return false;
        }

        // Get the settings HTML + JS
        HtmlStack::startJsBuffer();
        $settingsHtml = InputNamespace::namespaceInputs(fn () => (string) $widget->getSettingsHtml(), "widget$widget->id-settings");
        $settingsJs = HtmlStack::clearJsBuffer(false);

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
            'settingsHtml' => $settingsHtml,
            'settingsJs' => (string) $settingsJs,
            'settings' => $widget->getSettings(),
        ];
    }
}
