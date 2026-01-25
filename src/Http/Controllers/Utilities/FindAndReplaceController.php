<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class FindAndReplaceController
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
            'params.find' => ['required', 'string'],
            'params.replace' => ['required', 'string'],
        ])['params'];

        dispatch(new FindAndReplace(
            find: $params['find'],
            replace: $params['replace']
        ));

        return new JsonResponse;
    }
}
