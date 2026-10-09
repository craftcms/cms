<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use Illuminate\Http\Request;

readonly class NormalizeLegacyPath
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->uri()->path() !== 'index.php' || !$request->has('p')) {
            return $next($request);
        }

        $query = $request->query->all();
        unset($query['p']);

        $normalizedRequest = $request->duplicateWithUri((string)$request->get('p'), $query);
        $normalizedRequest->request->remove('p');

        app()->instance('request', $normalizedRequest);

        return $next($normalizedRequest);
    }
}
