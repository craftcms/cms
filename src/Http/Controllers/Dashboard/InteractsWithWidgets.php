<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;

trait InteractsWithWidgets
{
    protected function getWidgetIconSvg(WidgetInterface $widget): ?string
    {
        $icon = $widget::icon();
        $label = $widget::displayName();

        return $icon ? Icons::svg($icon, $label) : Icons::fallbackSvg($label);
    }

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

        if (($maxColspan = $widget::maxColspan()) && $colspan > $maxColspan) {
            $colspan = $maxColspan;
        }

        return [
            'id' => $widget->id,
            'type' => $widget::class,
            'colspan' => $colspan,
            'title' => $widget->getTitle(),
            'subtitle' => $widget->getSubtitle(),
            'name' => $widget->displayName(),
            'bodyHtml' => $widgetBodyHtml,
            'settings' => $widget->getSettings(),
            ...$settings,
        ];
    }

    protected function getWidgetSettingsInfo(WidgetInterface $widget, string $namespace): array
    {
        $definition = InputNamespace::with(
            $namespace,
            fn (): ?array => $widget->getSettingsFormDefinition(false)?->toArray(),
        );
        $values = $widget->getSettings();

        if ($widget instanceof QuickPost && $definition !== null) {
            foreach ($definition['elements'] as $field) {
                $name = $field['children'][0]['name'] ?? null;

                if (! is_string($name) || ! str_starts_with($name, 'sections.')) {
                    continue;
                }

                [, $sectionId] = explode('.', $name);
                $value = (int) $sectionId === $widget->section
                    ? $widget->entryType
                    : ($field['children'][0]['props']['options'][0]['value'] ?? null);
                Arr::set($values, $name, $value);
            }
        }

        $errors = [];

        foreach ($widget->errors()->getMessages() as $attribute => $messages) {
            $errors["{$namespace}.{$attribute}"] = $messages;
        }

        return [
            'settingsDefinition' => $definition,
            'settingsValues' => [$namespace => $values],
            'settingsErrors' => $errors,
            'settingsBindingScope' => $namespace,
            'settingsInputNamespace' => $namespace,
            'settingsReadOnly' => false,
        ];
    }
}
