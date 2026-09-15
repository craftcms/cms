<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Data\WidgetData;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
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

        $settingsForm = $this->getWidgetSettingsForm($widget, "widget{$widget->id}-settings");

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
            settingsForm: $settingsForm,
        );
    }

    protected function getWidgetIconSvg(WidgetInterface $widget): ?string
    {
        $icon = $widget->getIcon();
        $label = $widget->getDisplayName();

        return $icon ? Icons::svg($icon, $label) : Icons::fallbackSvg($label);
    }

    protected function getWidgetSettingsForm(WidgetInterface $widget, string $namespace): ?FormPayload
    {
        $context = new FormContext(
            namespace: $namespace,
            values: [$namespace => $widget->getSettings()],
            errors: $widget->errors()->getMessages(),
            refreshable: true,
        );
        $form = $widget->settingsForm($context);

        return $form === null ? null : app(FormResolver::class)->resolve($form, $context);
    }
}
