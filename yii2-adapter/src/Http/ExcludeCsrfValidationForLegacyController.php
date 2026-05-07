<?php

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Craft;
use craft\web\Controller;
use Illuminate\Http\Request;

class ExcludeCsrfValidationForLegacyController
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->isActionRequest()) {
            return $next($request);
        }

        $result = Craft::$app?->createController(implode('/', $request->actionSegments()));
        $controller = is_array($result) ? $result[0] : null;

        if ($controller instanceof Controller && !$controller->enableCsrfValidation) {
            $controller->registerCsrfValidationExclusion();
        }

        return $next($request);
    }
}
