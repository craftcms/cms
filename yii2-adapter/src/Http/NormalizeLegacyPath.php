<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use Closure;
use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;
use Illuminate\Http\Request;

readonly class NormalizeLegacyPath
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->uri()->path() !== 'index.php' || !$request->has('p')) {
            return $next($request);
        }

        LegacyUsage::record(
            'request.index-php-path',
            'Requests use `index.php?p=` URLs. Configure the web server to rewrite requests to `index.php`, and use path URLs.',
            requestLevel: true,
        );

        $query = $request->query->all();
        unset($query['p']);

        $normalizedRequest = $request->duplicateWithUri((string)$request->get('p'), $query);
        $normalizedRequest->request->remove('p');

        app()->instance('request', $normalizedRequest);

        return $next($normalizedRequest);
    }
}
