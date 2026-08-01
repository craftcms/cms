<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\DashboardAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

use function CraftCms\Cms\cp_url;

readonly class DashboardController
{
    use InteractsWithWidgets;

    public function __construct(
        private HtmlStack $HtmlStack,
        private Dashboard $dashboard,
        private WidgetTypes $widgetTypes,
    ) {}

    public function index()
    {
        /**
         * @var Collection<string, array<string, mixed>> $widgetTypeInfo
         */
        $widgetTypeInfo = $this->widgetTypes->types()
            /** @var class-string<WidgetInterface> $widgetType */
            ->filter(fn (string $widgetType) => $widgetType::isSelectable())
            ->mapWithKeys(function (string $widgetType) {
                $widget = $this->dashboard->createWidget($widgetType);

                return [$widget::class => [
                    'iconSvg' => $this->getWidgetIconSvg($widget),
                    'name' => $widget::displayName(),
                    'maxColspan' => $widget::maxColspan(),
                    'selectable' => true,
                    ...$this->getWidgetSettingsInfo($widget, '__NAMESPACE__'),
                ]];
            })
            ->sortBy('name');

        $variables = [];
        // Assemble the list of existing widgets
        $variables['widgets'] = [];
        $allWidgetJs = '';

        $this->dashboard->getAllWidgets()
            ->each(function (WidgetInterface $widget) use ($widgetTypeInfo, &$variables, &$allWidgetJs) {
                $this->HtmlStack->startJsBuffer();
                $info = $this->getWidgetInfo($widget);
                $widgetJs = $this->HtmlStack->clearJsBuffer(false);

                if ($info === false) {
                    return;
                }

                if (! $widgetTypeInfo->has($info['type'])) {
                    $widgetTypeInfo->put($info['type'], [
                        'iconSvg' => $this->getWidgetIconSvg($widget),
                        'name' => $widget::displayName(),
                        'maxColspan' => $widget::maxColspan(),
                        'selectable' => false,
                    ]);
                }

                $variables['widgets'][] = $info;
                $allWidgetJs .= 'new Craft.Widget("#widget'.$widget->id.'", '.
                    Json::encode($this->settingsContext($info)).','.
                    Json::encode($info['settings']).
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

    private function settingsContext(array $info): ?array
    {
        if ($info['settingsDefinition'] === null) {
            return null;
        }

        return [
            'definition' => $info['settingsDefinition'],
            'values' => $info['settingsValues'],
            'errors' => $info['settingsErrors'],
            'bindingScope' => $info['settingsBindingScope'],
            'inputNamespace' => $info['settingsInputNamespace'],
            'readOnly' => $info['settingsReadOnly'],
        ];
    }
}
