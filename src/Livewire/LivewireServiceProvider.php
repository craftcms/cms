<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use Override;

/**
 * Integrates Livewire with Craft’s site routes and templates when it’s installed.
 *
 * @since 6.0.0
 */
class LivewireServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        // Livewire subscribes the component hooks registered by the time it boots, which can be
        // before this provider boots, so this one is registered before any provider boots.
        if (class_exists(ComponentHookRegistry::class)) {
            ComponentHookRegistry::register(SupportCraftSites::class);
        }
    }

    public function boot(): void
    {
        if (! class_exists(Livewire::class) || ! $this->app->bound('livewire')) {
            return;
        }

        Livewire::addNamespace(LivewireComponents::NAMESPACE, viewPath: Path::siteTemplates());
        Livewire::propertySynthesizer(ElementSynth::class);

        Twig::registerExtension(new LivewireTwigExtension, TemplateMode::Site);

        // Livewire registers one update route for every component in the app, with only the `web` group.
        // This adds Craft's middleware to updates for components rendered on Craft routes only. It goes on
        // Livewire's default route and any custom one set with `Livewire::setUpdateRoute()`, which Livewire
        // uses instead when there is one.
        $this->app->booted(function (): void {
            collect(Route::getRoutes()->getRoutes())
                ->filter(fn (IlluminateRoute $route): bool => Str::endsWith((string) $route->getName(), 'livewire.update'))
                // ApplyCraftMiddleware checks maintenance mode itself, once it knows whose component it is.
                ->each(fn (IlluminateRoute $route) => $route
                    ->middleware(ApplyCraftMiddleware::class)
                    ->metadata([PreventRequestsDuringMaintenance::ALLOW_DURING_MAINTENANCE_METADATA => true]));

            // Craft listed the routes that allow maintenance before these were marked. Cached routes were
            // listed after the app booted, so they already include them.
            if (! $this->app->routesAreCached()) {
                PreventRequestsDuringMaintenance::registerRouteExceptions();
            }
        });
    }
}
