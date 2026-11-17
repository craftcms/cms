<?php

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Craft;
use craft\web\Controller;
use Illuminate\Http\Request;
use Throwable;

class ExcludeCsrfValidationForLegacyController
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->method(), ['HEAD', 'GET', 'OPTIONS'])) {
            return $next($request);
        }

        $route = $this->resolveRoute($request);

        if ($route !== '') {
            $this->csrfExemptController($route)?->registerCsrfValidationExclusion();
        }

        return $next($request);
    }

    /**
     * Returns whether the legacy controller action for a route disables CSRF validation,
     * either on the controller or from its `beforeAction()`.
     */
    public function disablesCsrfValidation(string $route): bool
    {
        return $this->csrfExemptController($route) !== null;
    }

    private function csrfExemptController(string $route): ?Controller
    {
        $result = Craft::$app?->createController($route);
        $controller = is_array($result) ? $result[0] : null;
        $actionId = is_array($result) ? $result[1] : null;

        if (!$controller instanceof Controller) {
            return null;
        }

        if ($controller->enableCsrfValidation) {
            $this->prepareControllerAction($controller, $actionId);
        }

        return $controller->enableCsrfValidation ? null : $controller;
    }

    private function resolveRoute(Request $request): string
    {
        if ($request->isActionRequest()) {
            return implode('/', $request->actionSegments());
        }

        try {
            return Craft::$app?->getRequest()->resolve()[0] ?? $request->craftPath();
        } catch (Throwable) {
            return $request->craftPath();
        }
    }

    private function prepareControllerAction(Controller $controller, ?string $actionId): void
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
