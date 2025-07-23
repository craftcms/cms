<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use craft\helpers\Queue;
use craft\queue\jobs\FindAndReplace;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FindAndReplaceController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(Utilities\FindAndReplace::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request)
    {
        $params = $request->validate([
            'params' => ['required', 'array'],
            'params.find' => ['string', 'nullable'],
            'params.replace' => ['string', 'nullable'],
        ])['params'];

        if (! empty($params['find']) && ! empty($params['replace'])) {
            Queue::push(new FindAndReplace([
                'find' => $params['find'],
                'replace' => $params['replace'],
            ]));
        }

        return new JsonResponse;
    }
}
