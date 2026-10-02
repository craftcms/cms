<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Concerns;

use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;
use Illuminate\Http\RedirectResponse;

/**
 * Sends a bare index to the URL of the source it shows.
 *
 * With no source named, an index shows its first source, but at its own URL —
 * which the nav can only match to the index's item, so that's what it
 * highlights. Redirecting to the link the nav gives the source lands on the
 * source instead, the way the Entries index does by sharing its URL with “All
 * entries”, and the Assets index by redirecting to its first volume.
 */
trait RedirectsToShownSource
{
    /**
     * @param  bool  $sourceNamed  Whether the URL already names a source — by
     *                             a section handle or a user slug, say.
     */
    private function shownSourceRedirect(
        ElementIndexRequest $request,
        ContentIndexViewModel $viewModel,
        bool $sourceNamed,
    ): ?RedirectResponse {
        if ($sourceNamed || $request->filled('source')) {
            return null;
        }

        $key = $viewModel->source()['key'] ?? null;

        if (! is_string($key) || $key === '') {
            return null;
        }

        $url = app(Navigation::class)->sourceUrl($viewModel->elementType(), $key);

        if ($url === null) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        // Already there: the source's link is the index's own URL.
        if (rtrim($path, '/') === rtrim('/'.ltrim($request->path(), '/'), '/') && ! isset($query['source'])) {
            return null;
        }

        $query = [...$request->query(), ...$query];
        $base = strtok($url, '?');

        return redirect($query === [] ? $base : $base.'?'.http_build_query($query));
    }
}
