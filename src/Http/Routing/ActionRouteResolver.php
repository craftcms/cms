<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Routing;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use Illuminate\Http\Request;

readonly class ActionRouteResolver
{
    public function resolve(Request $request): ?ActionRoute
    {
        $cached = $request->attributes->get(ActionRoute::class);

        if ($cached instanceof ActionRoute) {
            return $cached;
        }

        $segments = $this->segmentsFromPath($request);

        if ($segments === null && ! $request->attributes->get(HandleTokenRequest::ROUTE_RESOLVED_ATTRIBUTE, false)) {
            $segments = $this->segmentsFromActionParam($request);
        }

        if ($segments === null) {
            return null;
        }

        $actionRoute = ActionRoute::fromSegments($segments, $request->isCpRequest());

        if ($actionRoute !== null) {
            $request->attributes->set(ActionRoute::class, $actionRoute);
        }

        return $actionRoute;
    }

    /** @return list<string>|null */
    private function segmentsFromPath(Request $request): ?array
    {
        $actionTrigger = trim(Cms::config()->actionTrigger, '/');
        $segmentIndex = $this->actionTriggerSegmentIndex($request);

        if ($request->segment($segmentIndex) === $actionTrigger && count($request->segments()) > $segmentIndex) {
            return array_slice($request->segments(), $segmentIndex);
        }

        return null;
    }

    /** @return list<string>|null */
    private function segmentsFromActionParam(Request $request): ?array
    {
        $actionParam = $request->input('action');

        if ($actionParam === null) {
            return null;
        }

        if (! is_string($actionParam)) {
            abort(400, 'Invalid action param');
        }

        $segments = array_values(array_filter(explode('/', $actionParam)));

        return $segments === [] ? null : $segments;
    }

    private function actionTriggerSegmentIndex(Request $request): int
    {
        if (! $request->isCpRequest()) {
            return 1;
        }

        return trim((string) Cms::config()->cpTrigger, '/') === '' ? 1 : 2;
    }
}
