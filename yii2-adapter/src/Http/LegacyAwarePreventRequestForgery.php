<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Craft;
use craft\web\Controller;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\Middleware\PreventRequestForgery;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Exceptions\OriginMismatchException;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Override;
use Throwable;

/**
 * Honors `craft\web\Controller::$enableCsrfValidation = false` for legacy controller actions,
 * whether a controller sets it on the class or from its `beforeAction()`.
 *
 * Legacy actions run after this middleware, so the opt-out is checked for the current request
 * only, once the origin and token checks have failed. Requests that pass those checks never
 * call the controller’s `beforeAction()` ahead of the action, so it runs once for them. In
 * origin-only mode, the origin check rejects the request before tokens are checked, so the
 * opt-out is checked there instead.
 *
 * Actions reached through site URL rules (such as `api/webhook`) are checked against the rule
 * that {@see HandleYiiSiteRouteFallback} would run, and no path is excluded for other requests
 * or sites.
 */
class LegacyAwarePreventRequestForgery extends PreventRequestForgery
{
    #[Override]
    protected function hasValidOrigin($request): bool
    {
        try {
            return parent::hasValidOrigin($request);
        } catch (OriginMismatchException $e) {
            if ($this->legacyRouteDisablesCsrfValidation($request)) {
                return true;
            }

            throw $e;
        }
    }

    protected function tokensMatch($request)
    {
        return parent::tokensMatch($request) || $this->legacyRouteDisablesCsrfValidation($request);
    }

    private function legacyRouteDisablesCsrfValidation(Request $request): bool
    {
        if (!$request->routeIs('craft.legacy.action', 'craft.cp.legacy.action', 'craft.cp.legacy', 'craft.siteFallback')) {
            return false;
        }

        try {
            // Legacy routes depend on the current site and the Yii request, which may be set up after this middleware.
            return $this->app->make(Pipeline::class)
                ->send($request)
                ->through([ResolveSite::class, PrepareLegacyCraftApp::class])
                ->then(fn(Request $request) => $request->routeIs('craft.siteFallback')
                    ? $this->siteRouteDisablesCsrfValidation($request)
                    : $this->controllerDisablesCsrfValidation($this->legacyRoute($request)));
        } catch (Throwable) {
            return false;
        }
    }

    private function legacyRoute(Request $request): string
    {
        if ($request->isActionRequest()) {
            return implode('/', $request->actionSegments());
        }

        return Craft::$app->getRequest()->resolve()[0] ?? '';
    }

    private function siteRouteDisablesCsrfValidation(Request $request): bool
    {
        $route = Craft::$app->getUrlManager()->parseRequest(Craft::$app->getRequest());

        if ($route === false || !$this->controllerDisablesCsrfValidation($route[0])) {
            return false;
        }

        if (Cms::config()->headlessMode) {
            return true;
        }

        // An element with a route at this URI takes precedence over the URL rules.
        $sites = $this->app->make(Sites::class);
        $element = $this->app->make(Elements::class)->getElementByUri($sites->getRequestPath($request), $sites->getCurrentSite()->id, true);

        return !$element?->getRoute();
    }

    /**
     * Returns whether the legacy controller action for a route disables CSRF validation,
     * either on the controller or from its `beforeAction()`.
     */
    private function controllerDisablesCsrfValidation(string $route): bool
    {
        if ($route === '') {
            return false;
        }

        [$controller, $actionId] = Craft::$app->createController($route) ?: [null, null];

        if (!$controller instanceof Controller) {
            return false;
        }

        if ($controller->enableCsrfValidation) {
            $this->prepareControllerAction($controller, $actionId);
        }

        return !$controller->enableCsrfValidation;
    }

    private function prepareControllerAction(Controller $controller, string $actionId): void
    {
        $action = $controller->createAction($actionId ?: $controller->defaultAction);

        if ($action === null) {
            return;
        }

        $oldAction = $controller->action;
        $controller->action = $action;

        try {
            $controller->beforeAction($action);
        } catch (Throwable) {
            // Normal request handling will surface controller guard failures.
        } finally {
            $controller->action = $oldAction;
        }
    }
}
