<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Data\WidgetData;
use CraftCms\Cms\Dashboard\Widgets\Custom;
use CraftCms\Cms\Dashboard\Widgets\MissingWidget;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\View\HtmlStack;

trait InteractsWithWidgets
{
    /**
     * Returns whether the current user is allowed to use an existing widget.
     */
    protected function canUseWidget(WidgetInterface $widget): bool
    {
        // Missing widgets are still shown, so they can be deleted.
        // Custom widgets are available to everyone, and render nothing once their definition is gone.
        return $widget instanceof MissingWidget
            || $widget instanceof Custom
            || app(Dashboard::class)->isWidgetTypeAvailable($widget::class);
    }

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
            color: Color::tryFrom((string) $widget->color),
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
