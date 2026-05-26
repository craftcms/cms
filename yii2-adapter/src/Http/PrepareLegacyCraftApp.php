<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Craft;
use craft\helpers\App;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

readonly class PrepareLegacyCraftApp
{
    public function __construct(
        private Application $app,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $this->restoreCraftApp();
        $this->refreshRequestScopedComponents($request);

        return $next($request);
    }

    private function restoreCraftApp(): void
    {
        if (class_exists(Craft::class, false) && Craft::$app) {
            return;
        }

        $craftApp = $this->app->make('Craft');

        if (!Craft::$app) {
            Craft::$app = $craftApp;
        }
    }

    private function refreshRequestScopedComponents(Request $request): void
    {
        $this->app->instance('request', $request);

        /** @var \craft\web\Request $yiiRequest */
        $yiiRequest = Craft::createObject(App::webRequestConfig());
        $yiiRequest->csrfCookie = Craft::cookieConfig([], $yiiRequest);

        Craft::$app->set('request', $yiiRequest);
        Craft::$app->set('view', Craft::createObject(App::viewConfig()));
        Craft::$app->set('user', Craft::createObject(App::userConfig()));
    }
}
