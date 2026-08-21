<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Forms\FormKitchenSink;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(Plugins::class)->addViteConfig('workbench', [
            'hotFile' => CmsAssets::resourcesPath('hot'),
            'buildDirectory' => 'vendor/craft/build',
            'input' => ['workbench/resources/js/cp.ts'],
        ]);

        Event::listen(function (CpNavItemsResolving $event): void {
            $subnav = [];

            foreach (FormKitchenSink::COMPONENTS as $type => $components) {
                foreach ($components as $slug => $component) {
                    $label = Str::headline(class_basename($component));
                    $subnav[] = new NavItem()
                        ->label("{$label} ".Str::singular($type))
                        ->url("workbench/forms/{$type}/{$slug}");
                }
            }

            $event->navItems[] = new NavItem()
                ->label('Kitchen Sink')
                ->url('workbench/forms')
                ->icon('flask')
                ->subnav($subnav);
        });
    }
}
