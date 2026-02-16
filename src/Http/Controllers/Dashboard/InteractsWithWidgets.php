<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use craft\helpers\Cp;
use craft\web\View;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Support\Facades\InputNamespace;

trait InteractsWithWidgets
{
    protected readonly View $view;

    protected function getWidgetIconSvg(WidgetInterface $widget): ?string
    {
        $icon = $widget::icon();
        $label = $widget::displayName();

        return $icon ? Cp::iconSvg($icon, $label) : Cp::fallbackIconSvg($label);
    }

    protected function getWidgetInfo(WidgetInterface $widget): array|false
    {
        // Get the body HTML
        $widgetBodyHtml = $widget->getBodyHtml();

        if ($widgetBodyHtml === null) {
            return false;
        }

        // Get the settings HTML + JS
        $this->view->startJsBuffer();
        $settingsHtml = InputNamespace::namespaceInputs(fn () => (string) $widget->getSettingsHtml(), "widget$widget->id-settings");
        $settingsJs = $this->view->clearJsBuffer(false);

        // Get the colspan (limited to the widget type's max allowed colspan)
        $colspan = $widget->colspan ?? 1;
        $colspan = min($colspan, $widget::maxColspan() ?? 3);

        return [
            'id' => $widget->id,
            'type' => $widget::class,
            'colspan' => $colspan,
            'title' => $widget->getTitle(),
            'subtitle' => $widget->getSubtitle(),
            'name' => $widget->displayName(),
            'bodyHtml' => $widgetBodyHtml,
            'settingsHtml' => $settingsHtml,
            'settingsJs' => (string) $settingsJs,
            'settings' => $widget->getSettings(),
        ];
    }
}
