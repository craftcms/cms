<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class ClearCachesController
{
    use RespondsWithFlash;

    public function __construct(Utilities $utilitiesService)
    {
        if (! $utilitiesService->checkAuthorization(ClearCaches::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function clearCaches(Request $request): Response
    {
        $caches = $request->validate([
            'caches' => ['required'],
        ])['caches'];

        ClearCaches::clear(is_array($caches) ? array_values($caches) : '*');

        // The utility's form announces the result itself; a JSON caller, such
        // as the command palette, is told what happened.
        return $request->expectsJson() ? $this->asJsonSuccess(t('Caches cleared.')) : back();
    }

    public function invalidateTags(Request $request, ElementCaches $elementCaches): Response
    {
        $tags = $request->validate([
            'tags' => ['required', 'array'],
            'tags.*' => ['string'],
        ])['tags'];

        try {
            $elementCaches->invalidateTagsOrFail(array_values($tags));
        } catch (Throwable $e) {
            report($e);
            $message = t('Couldn’t invalidate the caches.');

            // As validation errors, so the utility's form shows the failure instead of its success message.
            return $this->asFailure($message, ['errors' => ['tags' => [$message]]]);
        }

        return back();
    }
}
