<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Data\WidgetData;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\View\HtmlStack;

trait InteractsWithWidgets
{
    protected function getWidgetData(WidgetInterface $widget): WidgetData|false
    {
        $htmlStack = app(HtmlStack::class);
        $component = null;
        $data = [];
        $fragment = $htmlStack->capture(function () use ($widget, &$component, &$data): string {
            $component = $widget->component();

            if ($component === null) {
                return '';
            }

            $data = $widget->props();

            return $component === 'craft:html-widget' ? ($data['html'] ?? '') : '';
        });

        if ($component === null) {
            return false;
        }

        $settingsUi = $this->getWidgetSettingsUi($widget, "widget{$widget->id}-settings");

        return new WidgetData(
            id: $widget->id,
            type: $widget->getType(),
            colspan: min($widget->colspan ?: 1, $widget->getMaxColspan() ?: 4),
            maxColspan: $widget->getMaxColspan() ?: 4,
            title: $widget->getTitle(),
            subtitle: $widget->getSubtitle(),
            name: $widget->getDisplayName(),
            settings: $widget->getSettings(),
            component: $component,
            data: $data,
            fragment: $fragment,
            settingsUi: $settingsUi,
        );
    }

    protected function getWidgetIconSvg(WidgetInterface $widget): ?string
    {
        $icon = $widget->getIcon();
        $label = $widget->getDisplayName();

        return $icon ? Icons::svg($icon, $label) : Icons::fallbackSvg($label);
    }

    protected function getWidgetSettingsUi(WidgetInterface $widget, string $namespace): ?UiPayload
    {
        $context = new UiContext(
            namespace: $namespace,
            values: [$namespace => $widget->getSettings()],
            errors: $widget->errors()->getMessages(),
            refreshable: true,
        );
        $ui = $widget->settingsUi($context);

        return $ui === null ? null : app(UiResolver::class)->resolve($ui, $context);
    }
}
