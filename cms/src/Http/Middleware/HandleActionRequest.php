<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use yii\web\NotFoundHttpException;

final readonly class HandleActionRequest
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        /** Laravel found this route */
        if ($response instanceof Response && $response->getStatusCode() !== 404) {
            return $response;
        }

        if (! $request->isActionRequest()) {
            return $response;
        }

        $actionSegments = $request->actionSegments();
        $route = implode('/', array_filter([
            '',
            $request->isCpRequest() ? $this->generalConfig->cpTrigger : null,
            $this->generalConfig->actionTrigger,
            ...$actionSegments,
        ], fn ($value) => ! is_null($value)));

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
