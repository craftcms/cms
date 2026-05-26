<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Routing;

use CraftCms\Cms\Cms;
use Illuminate\Http\Request;

readonly class ActionRouteResolver
{
    public function resolve(Request $request): ?ActionRoute
    {
        $cached = $request->attributes->get(ActionRoute::class);

        if ($cached instanceof ActionRoute) {
            return $cached;
        }

        $segments = $this->segmentsFromPath($request) ?? $this->segmentsFromActionParam($request);

        if ($segments === null) {
            return null;
        }

        $actionRoute = ActionRoute::fromSegments($segments, $request->isCpRequest());

        if ($actionRoute !== null) {
            $request->attributes->set(ActionRoute::class, $actionRoute);
        }

        return $actionRoute;
    }

    private function segmentsFromPath(Request $request): ?array
    {
        $actionTrigger = Cms::config()->actionTrigger;
        $segmentIndex = $request->isCpRequest() ? 2 : 1;

        if ($request->segment($segmentIndex) === $actionTrigger && count($request->segments()) > $segmentIndex) {
            return array_slice($request->segments(), $segmentIndex);
        }

        return null;
    }

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
}
