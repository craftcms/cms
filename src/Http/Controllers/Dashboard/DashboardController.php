<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use Craft;
use craft\web\assets\dashboard\DashboardAsset;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Collection;

final readonly class DashboardController
{
    use InteractsWithWidgets;

    public function __construct(
        private Dashboard $dashboard,
    ) {
        $this->view = Craft::$app->getView();
    }

    public function __invoke()
    {
        /**
         * @var Collection<string, array{iconSvg: mixed, name: string, maxColspan: int|null, settingsHtml?: string, settingsJs?: mixed, selectable: bool}> $widgetTypeInfo
         */
        $widgetTypeInfo = $this->dashboard->getAllWidgetTypes()
            /** @var class-string<WidgetInterface> $widgetType */
            ->filter(fn (string $widgetType) => $widgetType::isSelectable())
            /** @phpstan-ignore argument.unresolvableType */
            ->mapWithKeys(function (string $widgetType) {
                AssetRegistry::startJsBuffer();
                $widget = $this->dashboard->createWidget($widgetType);
                $settingsHtml = InputNamespace::namespaceInputs(fn () => (string) $widget->getSettingsHtml(), '__NAMESPACE__');
                $settingsJs = (string) AssetRegistry::clearJsBuffer(false);

                return [$widget::class => [
                    'iconSvg' => $this->getWidgetIconSvg($widget),
                    'name' => $widget::displayName(),
                    'maxColspan' => $widget::maxColspan(),
                    'settingsHtml' => $settingsHtml,
                    'settingsJs' => $settingsJs,
                    'selectable' => true,
                ]];
            })
            /** @phpstan-ignore argument.unresolvableType */
            ->sortBy('name');

        $variables = [];
        // Assemble the list of existing widgets
        $variables['widgets'] = [];
        $allWidgetJs = '';

        $this->dashboard->getAllWidgets()
            ->each(function (WidgetInterface $widget) use ($widgetTypeInfo, &$variables, &$allWidgetJs) {
                AssetRegistry::startJsBuffer();
                $info = $this->getWidgetInfo($widget);
                $widgetJs = AssetRegistry::clearJsBuffer(false);

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
                    Json::encode($info['settingsHtml']).', '.
                    '() => {'.$info['settingsJs'].'},'.
                    Json::encode($info['settings']).
                    ");\n";

                // Allow any widget JS to execute *after* we've created the Craft.Widget instance
                $allWidgetJs .= $widgetJs ? $widgetJs."\n" : '';
            });

        // Include all the JS and CSS stuff
        $this->view->registerAssetBundle(DashboardAsset::class);
        AssetRegistry::jsWithVars(
            fn ($widgetTypeInfo) => "window.dashboard = new Craft.Dashboard($widgetTypeInfo)",
            [$widgetTypeInfo]
        );
        AssetRegistry::js($allWidgetJs);

        $variables['widgetTypes'] = $widgetTypeInfo;

        return view('dashboard/_index', $variables);
    }
}
