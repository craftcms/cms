<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Craft;
use CraftCms\Cms\Route\MatchedElement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class HandleYiiSiteRouteFallback
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (!$response instanceof Response || $response->getStatusCode() !== 404) {
            return $response;
        }

        if (!$request->routeIs('craft.siteFallback') || !Craft::$app) {
            return $response;
        }

        $elementRoute = MatchedElement::getRoute();

        if ($elementRoute !== false && is_string($elementRoute[0] ?? null)) {
            $element = MatchedElement::get();
            $params = is_array($elementRoute[1] ?? null) ? $elementRoute[1] : [];

            return $this->run($elementRoute[0], compact('element') + $params);
        }

        $route = Craft::$app->getUrlManager()->parseRequest(Craft::$app->getRequest());

        return $route === false
            ? $response
            : $this->run($route[0], $route[1] ?? []);
    }

    private function run(string $route, array $params): Response
    {
        Craft::$app->runAction($route, $params);

        return LegacyMiddleware::createResponse();
    }
}
