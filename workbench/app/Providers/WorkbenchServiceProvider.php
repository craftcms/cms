<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\Controllers\ErrorPagesController;
use Workbench\App\Http\Controllers\LegacyPagesController;
use Workbench\App\Http\Controllers\PortedPagesController;
use Workbench\App\Ui\UiKitchenSink;
use Workbench\App\Widgets\HtmlExample;
use Workbench\App\Widgets\LayoutSlotsDemo;
use Workbench\App\Workflow\AutomaticApprovalStage;

use function Orchestra\Testbench\package_path;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $composer = Json::decode(
            file_get_contents(package_path('composer.json')),
        );

        AliasLoader::getInstance(
            $composer['extra']['laravel']['aliases'] ?? [],
        );
    }

    public function boot(): void
    {
        $this->app->booted(
            fn () => app(WorkflowStageTypes::class)->register(
                AutomaticApprovalStage::class,
            ),
        );

        if (! $this->app->runningUnitTests()) {
            app(WidgetTypes::class)->register(HtmlExample::class);
            app(WidgetTypes::class)->register(LayoutSlotsDemo::class);
        }

        Event::listen(function (CommandStarting $event): void {
            if (! str_starts_with($event->command, 'boost:')) {
                return;
            }

            app()->setBasePath(package_path());
            app()->useAppPath(package_path('src'));
        });

        app(Plugins::class)->addViteConfig('workbench', [
            'hotFile' => CmsAssets::resourcesPath('hot'),
            'buildDirectory' => 'vendor/craft/build',
            'input' => ['workbench/resources/js/cp.ts'],
        ]);

        Event::listen(function (CpNavItemsResolving $event): void {
            $subnav = [];

            foreach (UiKitchenSink::COMPONENTS as $type => $components) {
                foreach ($components as $slug => $component) {
                    $label = Str::headline(class_basename($component));
                    $subnav[] = new NavItem()
                        ->label("{$label} ".Str::singular($type))
                        ->href("workbench/ui/{$type}/{$slug}");
                }
            }

            $event->navItems[] = new NavItem()
                ->label('Debug')
                ->group(true)
                ->subnav([
                    new NavItem()
                        ->label('Kitchen Sink')
                        ->href('workbench/ui')
                        ->icon('flask')
                        ->subnav($subnav),
                    new NavItem()
                        ->label('Layout Slots')
                        ->href('workbench/layout-slots')
                        ->icon('table-layout'),
                    new NavItem()
                        ->label('Legacy Pages')
                        ->href('workbench/legacy-pages/dbupdate')
                        ->icon('clock-rotate-left')
                        ->subnav(collect(LegacyPagesController::PAGES)
                            ->map(fn (string $label, string $page) => new NavItem()
                                ->label($label)
                                ->href("workbench/legacy-pages/{$page}"))
                            ->values()
                            ->all()),
                    new NavItem()
                        ->label('Error Pages')
                        ->href('workbench/error-pages/404')
                        ->icon('triangle-exclamation')
                        ->subnav(collect(ErrorPagesController::PAGES)
                            ->map(fn (string $label, int|string $page) => new NavItem()
                                ->label($label)
                                ->href("workbench/error-pages/{$page}"))
                            ->values()
                            ->all()),
                    new NavItem()
                        ->label('Ported Pages')
                        ->href('workbench/ported-pages/updater-progress')
                        ->icon('arrow-right-arrow-left')
                        ->subnav(collect(PortedPagesController::PAGES)
                            ->map(fn (string $label, string $page) => new NavItem()
                                ->label($label)
                                ->href("workbench/ported-pages/{$page}"))
                            ->values()
                            ->all()),
                ]);
        });
    }
}
