<?php

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Illuminate\Http\Request;

class CaptureOriginalActionRequestUri
{
    public const ORIGINAL_ACTION_REQUEST_URI = '_craft_yii_original_action_request_uri';

    /**
     * The URI the browser actually asked for, on a request the legacy action
     * bridge re-dispatched under a synthesized `actions/…` URI.
     *
     * The mirror of the constant above: that one remembers a pretty URI when the
     * browser asked for an action URL, this one remembers it when the bridge
     * invented the action URL. Both answer the same question — what URI should
     * anything user-facing present? — so they live together.
     */
    public const BRIDGED_ORIGINAL_REQUEST_URI = '_craft_yii_bridged_original_request_uri';

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isActionRequest() && trim($request->path(), '/') !== trim($request->actionSegmentsToRoute(), '/')) {
            $request->attributes->set(self::ORIGINAL_ACTION_REQUEST_URI, $request->getRequestUri());
        }

        return $next($request);
    }
}
