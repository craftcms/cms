<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

readonly class FindAndReplaceController
{
    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(Utilities\FindAndReplace::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $params = $request->validate([
            'find' => ['required', 'string'],
            'replace' => ['required', 'string'],
        ]);

        dispatch(new FindAndReplace(
            find: $params['find'],
            replace: $params['replace']
        ));

        return back()->with('success', t('Replace job dispatched.'));
    }
}
