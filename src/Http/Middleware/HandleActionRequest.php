<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use yii\web\NotFoundHttpException;

readonly class HandleActionRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isActionRequest()) {
            return $next($request);
        }

        $route = $request->actionSegmentsToRoute();

        if ($request->path() === $route) {
            return $next($request);
        }

        $newRequest = $request->duplicate(server: array_merge($request->server->all(), [
            'REQUEST_URI' => $route,
        ]));

        try {
            /** @var Response $response */
            $response = $next($newRequest);

            return $response;
        } catch (NotFoundHttpException) {
            /**
             * If Yii returned with a Page not found. It needs to handle the
             * original request with an action body parameter.
             *
             * @todo Remove when cms is fully ported.
             */
            return $next($request);
        }
    }
}
