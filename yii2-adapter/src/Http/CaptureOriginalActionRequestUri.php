<?php

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Illuminate\Http\Request;

class CaptureOriginalActionRequestUri
{
    public const ORIGINAL_ACTION_REQUEST_URI = '_craft_yii_original_action_request_uri';

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isActionRequest() && trim($request->path(), '/') !== trim($request->actionSegmentsToRoute(), '/')) {
            $request->attributes->set(self::ORIGINAL_ACTION_REQUEST_URI, $request->getRequestUri());
        }

        return $next($request);
    }
}
