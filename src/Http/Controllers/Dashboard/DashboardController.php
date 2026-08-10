<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\CustomWidgets;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Data\CustomWidgetDefinition;
use CraftCms\Cms\Dashboard\Widgets\Custom;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\DashboardAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

use function CraftCms\Cms\cp_url;

readonly class DashboardController
{
    use InteractsWithWidgets;

    public function __construct(
        private HtmlStack $HtmlStack,
        private Dashboard $dashboard,
        private CustomWidgets $customWidgets,
        private WidgetTypes $widgetTypes,
    ) {}

    public function index(): View
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
                ...$this->getWidgetSettingsInfo($widget, '__NAMESPACE__'),
            ]);
        }

        $widgetTypeInfo = $widgetTypeInfo->sortBy(fn (array $info) => $info['name']);

        $variables = [];
        // Assemble the list of existing widgets
        $variables['widgets'] = [];
        $allWidgetJs = '';

        $widgets
            ->each(function (WidgetInterface $widget) use ($widgetTypeInfo, &$variables, &$allWidgetJs) {
                $this->HtmlStack->startJsBuffer();
                $info = $this->getWidgetInfo($widget);
                $widgetJs = $this->HtmlStack->clearJsBuffer(false);

                if ($info === false) {
                    return;
                }

                if (! isset($widgetTypeInfo[$info['type']])) {
                    $widgetTypeInfo[$info['type']] = [
                        'iconSvg' => $this->getWidgetIconSvg($widget),
                        'name' => $widget->getDisplayName(),
                        'maxColspan' => $widget->getMaxColspan(),
                        'settingsForm' => null,
                        'settingsHtml' => '',
                        'settingsJs' => '',
                        'selectable' => false,
                    ];
                }

                $variables['widgets'][] = $info;
                $allWidgetJs .= 'new Craft.Widget("#widget'.$widget->id.'", '.
                    Json::encode($info['settingsHtml']).', '.
                    '() => {'.$info['settingsJs'].'},'.
                    Json::encode($info['settings']).','.
                    Json::encode($info['settingsForm']).
                    ");\n";

                // Allow any widget JS to execute *after* we've created the Craft.Widget instance
                $allWidgetJs .= $widgetJs ? $widgetJs."\n" : '';
            });

        // Include all the JS and CSS stuff
        app(InternalAssetRegistry::class)->register(DashboardAsset::class);
        $this->HtmlStack->jsWithVars(
            fn ($widgetTypeInfo) => "window.dashboard = new Craft.Dashboard($widgetTypeInfo)",
            [$widgetTypeInfo]
        );
        $this->HtmlStack->js($allWidgetJs);

        $variables['widgetTypes'] = $widgetTypeInfo;

        return view('dashboard/_index', $variables);
    }

    public function redirect(): RedirectResponse
    {
        if ($path = Cms::config()->getPostCpLoginRedirect()) {
            return redirect(cp_url($path));
        }

        return redirect(route('craft.cp.dashboard'));
    }
}
