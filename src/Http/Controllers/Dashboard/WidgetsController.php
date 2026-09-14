<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\CustomWidgets;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\Custom;
use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Support\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

readonly class WidgetsController
{
    use InteractsWithWidgets;

    public function __construct(
        private Dashboard $dashboard,
        private CustomWidgets $customWidgets,
        private WidgetTypes $widgetTypes,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $type = (string) $data['type'];
        $widgetType = $this->widgetTypes->types()->first(fn (string $widgetType) => $widgetType === $type);
        $customDefinition = $widgetType ? null : $this->customWidgets->fromType($type);

        if (! $widgetType && ! $customDefinition) {
            throw ValidationException::withMessages([
                'type' => 'Invalid widget type.',
            ]);
        }

        $selectable = $widgetType
            ? $widgetType::isSelectable()
            : $this->dashboard->getAllWidgets()->doesntContain(
                fn (WidgetInterface $widget) => $widget instanceof Custom && $widget->definitionId === $customDefinition->id,
            );

        if (! $selectable) {
            throw ValidationException::withMessages(['type' => 'This widget cannot be added.']);
        }

        $settings = $data['settings'] ?? [];

        $widget = $customDefinition
            ? $this->dashboard->createWidget([
                'type' => Custom::class,
                'settings' => [
                    'definitionId' => $customDefinition->id,
                ],
            ])
            : $this->dashboard->createWidget([
                'type' => $widgetType,
                'settings' => $settings,
            ]);

        return $this->saveAndReturnWidget($widget);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['sometimes', 'array'],
            'widgetId' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->craftUser()?->getCraftUserId()),
            ],
        ]);

        $widget = $this->dashboard->getWidgetById($request->integer('widgetId'));

        // Create a new widget model with the new settings
        $settings = $widget instanceof Custom
            ? $widget->getSettings()
            : $request->input('settings', []);

        Validator::validate($settings, $widget->getRules());

        $widget = $this->dashboard->createWidget([
            'id' => $widget->id,
            'colspan' => $widget->colspan,
            'type' => $widget::class,
            'settings' => $settings,
        ]);

        return $this->saveAndReturnWidget($widget);
    }

    public function refreshSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
            'namespace' => ['required', 'string'],
        ]);
        $type = (string) $data['type'];

        $widgetType = $this->widgetTypes->types()->first(fn (string $widgetType): bool => $widgetType === $type);

        if ($widgetType === null) {
            throw ValidationException::withMessages([
                'type' => 'Invalid widget type.',
            ]);
        }

        $widget = $this->dashboard->createWidget([
            'type' => $widgetType,
            'settings' => $data['settings'] ?? [],
        ]);

        return new JsonResponse([
            'form' => $this->getWidgetSettingsForm($widget, $data['namespace']),
        ]);
    }

    public function updateColspan(Request $request): JsonResponse
    {
        $request->validate([
            'id' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->craftUser()?->getCraftUserId()),
            ],
            'colspan' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        $this->dashboard->changeWidgetColspan($request->input('id'), $request->input('colspan'));

        return new JsonResponse;
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = Json::decode($request->input('ids'));

        Validator::validate(['ids' => $ids], [
            'ids' => ['required', 'array'],
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->craftUser()?->getCraftUserId()),
            ],
        ]);

        $this->dashboard->reorderWidgets($ids);

        return new JsonResponse;
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->craftUser()?->getCraftUserId()),
            ],
        ]);

        if (! $this->dashboard->deleteWidgetById($request->integer('id'))) {
            throw ValidationException::withMessages(['widget' => 'Couldn’t delete widget.']);
        }

        return new JsonResponse;
    }

    private function saveAndReturnWidget(WidgetInterface $widget): JsonResponse
    {
        if (! $this->dashboard->saveWidget($widget)) {
            throw ValidationException::withMessages(['widget' => 'Couldn’t save widget.']);
        }

        return new JsonResponse(['info' => $this->getWidgetData($widget)]);
    }
}
