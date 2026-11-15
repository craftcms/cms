<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Site\Sites;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Throwable;

/**
 * Honors `craft\web\Controller::$enableCsrfValidation = false` for legacy controller actions
 * reached through site URL rules.
 *
 * {@see HandleYiiSiteRouteFallback} runs those actions after this middleware, and their public
 * paths (such as `api/webhook`) aren’t the controller routes that
 * {@see \craft\web\Controller::registerCsrfValidationExclusion()} excludes. The opt-out is
 * checked for the current request only, once the origin and token checks have failed, so
 * requests with a valid token don’t resolve the legacy route twice, and no path is excluded
 * for other requests or sites.
 */
class LegacyAwarePreventRequestForgery extends PreventRequestForgery
{
    protected function tokensMatch($request)
    {
        return parent::tokensMatch($request) || $this->legacySiteRouteDisablesCsrfValidation($request);
    }

    private function legacySiteRouteDisablesCsrfValidation(Request $request): bool
    {
        if (!$request->routeIs('craft.siteFallback')) {
            return false;
        }

        try {
            // The site URL rules depend on the current site and the Yii request, which are set up after this middleware.
            return $this->app->make(Pipeline::class)
                ->send($request)
                ->through([ResolveSite::class, PrepareLegacyCraftApp::class])
                ->then(fn(Request $request) => $this->siteRouteDisablesCsrfValidation($request));
        } catch (Throwable) {
            return false;
        }
    }

    private function siteRouteDisablesCsrfValidation(Request $request): bool
    {
        $route = Craft::$app->getUrlManager()->parseRequest(Craft::$app->getRequest());

        if ($route === false || !$this->app->make(ExcludeCsrfValidationForLegacyController::class)->disablesCsrfValidation($route[0])) {
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
}
