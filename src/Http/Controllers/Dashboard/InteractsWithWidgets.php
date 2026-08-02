<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
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

    /**
     * @return array{
     *     settingsForm: array{elements: list<array<string, mixed>>}|null,
     *     settingsValues: array<string, array<string, mixed>>,
     *     settingsErrors: array<string, string[]>,
     *     settingsBindingScope: string,
     *     settingsInputNamespace: string,
     *     settingsReadOnly: bool,
     * }
     */
    protected function getWidgetSettingsInfo(WidgetInterface $widget, string $namespace): array
    {
        $form = InputNamespace::with(
            $namespace,
            fn (): ?array => $widget->getSettingsForm(false)?->toArray(),
        );
        $values = $widget instanceof QuickPost
            ? $widget->getSettingsFormValues()
            : $widget->getSettings();

        $errors = [];

        foreach ($widget->errors()->getMessages() as $attribute => $messages) {
            $errors["{$namespace}.{$attribute}"] = $messages;
        }

        return [
            'settingsForm' => $form,
            'settingsValues' => [$namespace => $values],
            'settingsErrors' => $errors,
            'settingsBindingScope' => $namespace,
            'settingsInputNamespace' => $namespace,
            'settingsReadOnly' => false,
        ];
    }
}
