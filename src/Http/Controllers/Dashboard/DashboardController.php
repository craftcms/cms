<?php

namespace CraftCms\Cms\Http\Controllers\Dashboard;

use craft\web\Application;
use craft\web\assets\dashboard\DashboardAsset;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Give;
use Illuminate\Support\Collection;
use Inertia\Inertia;

final readonly class DashboardController
{
    use InteractsWithWidgets;

    public function __construct(
        private Dashboard $dashboard,
        #[Give('Craft')] Application $craft,
    ) {
        $this->view = $craft->getView();
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
                $this->view->startJsBuffer();
                $widget = $this->dashboard->createWidget($widgetType);
                $settingsHtml = $this->view->namespaceInputs(fn () => (string) $widget->getSettingsHtml(), '__NAMESPACE__');
                $settingsJs = (string) $this->view->clearJsBuffer(false);

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
                $this->view->startJsBuffer();
                $info = $this->getWidgetInfo($widget);
                $widgetJs = $this->view->clearJsBuffer(false);

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

        $variables['widgetTypes'] = $widgetTypeInfo;

        if (request()->has('legacy')) {
            // Include all the JS and CSS stuff
            $this->view->registerAssetBundle(DashboardAsset::class);
            $this->view->registerJsWithVars(
                fn ($widgetTypeInfo) => "window.dashboard = new Craft.Dashboard($widgetTypeInfo)",
                [$widgetTypeInfo]
            );
            $this->view->registerJs($allWidgetJs);

            return $this->view->renderPageTemplate('dashboard/_index.twig', $variables);
        }

        return Inertia::render('Dashboard', $variables);
    }
}
