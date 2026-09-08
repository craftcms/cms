<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\CustomWidgets;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Data\CustomWidgetDefinition;
use CraftCms\Cms\Dashboard\Data\WidgetTypeData;
use CraftCms\Cms\Dashboard\Widgets\Custom;
use CraftCms\Cms\Dashboard\WidgetTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;

readonly class DashboardController
{
    use InteractsWithWidgets;

    public function __construct(
        private Dashboard $dashboard,
        private CustomWidgets $customWidgets,
        private WidgetTypes $widgetTypes,
    ) {}

    public function index(): Response
    {
        $widgets = $this->dashboard->getAllWidgets();

        /** @var Collection<string, class-string<WidgetInterface>|array{type: class-string<Custom>, settings: array{definitionId: string}}> $widgetConfigs */
        $widgetConfigs = Collection::make();

        foreach ($this->widgetTypes->types() as $widgetType) {
            if ($widgetType::isSelectable()) {
                $widgetConfigs->put($widgetType, $widgetType);
            }
        }

        $this->customWidgets->all()
            ->reject(fn (CustomWidgetDefinition $definition) => $widgets->contains(
                fn (WidgetInterface $widget) => $widget instanceof Custom && $widget->definitionId === $definition->id,
            ))
            ->each(function (CustomWidgetDefinition $definition) use ($widgetConfigs) {
                $widgetConfigs->put($definition->type(), [
                    'type' => Custom::class,
                    'settings' => [
                        'definitionId' => $definition->id,
                    ],
                ]);
            });

        /** @var Collection<string, array<string, mixed>> $widgetTypeInfo */
        $widgetTypeInfo = Collection::make();

        foreach ($widgetConfigs as $type => $config) {
            $widget = $this->dashboard->createWidget($config);

            $widgetTypeInfo->put($type, [
                'iconSvg' => $this->getWidgetIconSvg($widget),
                'name' => $widget->getDisplayName(),
                'maxColspan' => $widget->getMaxColspan(),
                'selectable' => true,
                'settingsForm' => $this->getWidgetSettingsForm($widget, '__NAMESPACE__'),
            ]);
        }

        foreach ($widgets as $widget) {
            if (! $widgetTypeInfo->has($widget->getType())) {
                $widgetTypeInfo->put($widget->getType(), [
                    'iconSvg' => $this->getWidgetIconSvg($widget),
                    'name' => $widget->getDisplayName(),
                    'maxColspan' => $widget->getMaxColspan(),
                    'settingsForm' => null,
                    'selectable' => false,
                ]);
            }
        }

        $widgetTypeInfo = $widgetTypeInfo->sortBy(fn (array $info) => $info['name']);

        return Inertia::render('Dashboard', [
            'title' => t('Dashboard'),
            'widgets' => fn () => $widgets->map(fn (WidgetInterface $widget) => $this->getWidgetData($widget))->filter()->values(),
            'widgetTypes' => $widgetTypeInfo->map(fn (array $info) => new WidgetTypeData(...$info)),
        ]);
    }

    public function redirect(): RedirectResponse
    {
        if ($path = Cms::config()->getPostCpLoginRedirect()) {
            return redirect(cp_url($path));
        }

        return redirect(route('craft.cp.dashboard'));
    }
}
