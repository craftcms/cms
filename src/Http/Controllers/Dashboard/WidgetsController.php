<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use craft\web\Application;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class WidgetsController
{
    use InteractsWithWidgets;

    public function __construct(
        #[Give('Craft')] private Application $craft,
        private Dashboard $dashboard
    ) {
        $this->view = $craft->getView();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        /** @var class-string<WidgetInterface> $type */
        $type = $data['type'];

        if (! in_array($type, $this->dashboard->getAllWidgetTypes()->all())) {
            throw ValidationException::withMessages([
                'type' => 'Invalid widget type.',
            ]);
        }

        $settings = $data['settings'] ?? [];

        if (! $settings && $request->has('settingsNamespace')) {
            $settings = $request->input($request->input('settingsNamespace'));
        }

        $widget = $this->dashboard->createWidget([
            'type' => $type,
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
                Rule::exists('widgets', 'id')->where('userId', $request->user()->id),
            ],
        ]);

        $widget = $this->dashboard->getWidgetById($request->integer('widgetId'));

        // Create a new widget model with the new settings
        $settings = $request->input("widget{$widget->id}-settings");

        Validator::validate($settings, $widget::getRules());

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
        /**
         * For backwards compatibility, if the request came in to `/widgets/{widgetId}/update-colspan`,
         * we need to merge the `widgetId` into the request data.
         */
        if ($request->route()->parameter('widgetId') !== null) {
            $request->merge(['id' => (int) $request->route()->parameter('widgetId')]);
        }

        $request->validate([
            'id' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->user()->id),
            ],
            'colspan' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $this->dashboard->changeWidgetColspan($request->input('id'), $request->input('colspan'));

        return new JsonResponse;
    }

    public function reorder(Request $request): JsonResponse
    {
        $ids = $request->input('ids');

        Validator::validate(['ids' => $request->input('ids')], [], [
            'ids' => ['required', 'array'],
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->user()->id),
            ],
        ]);

        $this->dashboard->reorderWidgets($ids);

        return new JsonResponse;
    }

    public function delete(Request $request): JsonResponse
    {
        /**
         * For backwards compatibility, if the request came in to `DELETE /widgets/{widgetId}`,
         * we need to merge the `widgetId` into the request data.
         */
        if ($request->route()->parameter('widgetId') !== null) {
            $request->merge(['id' => (int) $request->route()->parameter('widgetId')]);
        }
        $request->validate([
            'id' => [
                'required',
                'integer',
                Rule::exists('widgets', 'id')->where('userId', $request->user()->id),
            ],
        ]);

        $this->dashboard->deleteWidgetById($request->input('id'));

        return new JsonResponse;
    }

    protected function saveAndReturnWidget(WidgetInterface $widget): JsonResponse
    {
        $this->dashboard->saveWidget($widget);

        $info = $this->getWidgetInfo($widget);
        $view = $this->craft->getView();

        return new JsonResponse([
            'info' => $info,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }
}
