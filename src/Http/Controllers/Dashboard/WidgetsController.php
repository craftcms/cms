<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\CustomWidgets;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\Custom;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

readonly class WidgetsController
{
    use InteractsWithWidgets;

    public function __construct(
        private HtmlStack $HtmlStack,
        private Dashboard $dashboard,
        private CustomWidgets $customWidgets,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $type = (string) $data['type'];
        $widgetType = $this->dashboard->getAllWidgetTypes()->first(fn (string $widgetType) => $widgetType === $type);
        $customDefinition = $widgetType ? null : $this->customWidgets->fromType($type);

        if (! $widgetType && ! $customDefinition) {
            throw ValidationException::withMessages([
                'type' => 'Invalid widget type.',
            ]);
        }

        $settings = $data['settings'] ?? [];

        if (! $settings && $request->has('settingsNamespace')) {
            $settings = $request->input($request->input('settingsNamespace'));
        }

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
            : $request->input("widget{$widget->id}-settings");

        Validator::validate($settings, $widget->getRules());

        $widget = $this->dashboard->createWidget([
            'id' => $widget->id,
            'colspan' => $widget->colspan,
            'type' => $widget::class,
            'settings' => $settings,
        ]);

        return $this->saveAndReturnWidget($widget);
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

        $this->dashboard->deleteWidgetById($request->integer('id'));

        return new JsonResponse;
    }

    private function saveAndReturnWidget(WidgetInterface $widget): JsonResponse
    {
        $this->dashboard->saveWidget($widget);

        $info = $this->getWidgetInfo($widget);

        return new JsonResponse([
            'info' => $info,
            'headHtml' => $this->HtmlStack->headHtml(),
            'bodyHtml' => $this->HtmlStack->bodyHtml(),
        ]);
    }
}
