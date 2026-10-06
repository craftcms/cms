<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\Responses\BridgedScreen;
use CraftCms\Cms\View\LegacyScreenFragments;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a Craft 5-era control panel screen through the Inertia shell.
 *
 * A screen that is just a template extending `_layouts/cp` has no response of
 * its own to carry its parts — a controller returns the rendered string and
 * that's that. So the layout hands its fragments to
 * {@see LegacyScreenFragments} instead of drawing a document, and this picks
 * them up on the way back out and renders the shell around them.
 *
 * Innermost in the `craft.cp` group on purpose: the response it builds has to
 * pass back out through `HandleInertiaRequests`, which does its own work on an
 * Inertia response.
 *
 * Nothing to detect here. A template that doesn't extend `_layouts/cp` never
 * reaches the collecting layout, so it collects nothing and its response goes
 * through untouched.
 *
 * @internal
 *
 * @deprecated Exists only while Twig-rendered screens render inside the Inertia shell.
 */
readonly class RenderBridgedScreen
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $fragments = app(LegacyScreenFragments::class);
        $collected = $fragments->fragments();

        /**
         * Reset either way: this request is done with the collector, and a
         * screen that already sent itself — one formatted as a Yii response,
         * which converts in place — must not be rendered a second time.
         */
        $fragments->reset();

        if ($collected === null) {
            return $response;
        }

        return BridgedScreen::response($request, $collected);
    }
}
