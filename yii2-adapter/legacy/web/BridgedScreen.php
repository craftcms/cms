<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use CraftCms\Cms\Http\Responses\BridgedScreen as Screen;
use yii\web\Response as YiiResponse;

/**
 * Puts a bridged screen's response onto a Yii response.
 *
 * The response itself is built by {@see Screen}, which every bridged screen
 * goes through whatever gathered its fragments. This exists only because a
 * screen that came through `asCpScreen()` is still being formatted as a Yii
 * response at that point, and has to carry the result out through Yii.
 *
 * @internal
 *
 * @deprecated Exists only while Craft 5-era screens render inside the Inertia shell.
 */
final class BridgedScreen
{
    /**
     * Replaces the response's body with the shell wrapped around `$variables`.
     *
     * @param array<string, mixed> $variables
     */
    public static function send(YiiResponse $response, array $variables): void
    {
        $built = Screen::response(request(), $variables);

        $response->format = YiiResponse::FORMAT_RAW;
        $response->content = $built->getContent();
        $response->setStatusCode($built->getStatusCode());

        /**
         * Only the headers that decide how the client reads this body. Copying
         * the rest would duplicate cookies Yii has already queued.
         *
         * `location` comes across for the hard-visit case, where the built
         * response is a redirect rather than a rendered page.
         */
        foreach (['content-type', 'vary', 'x-inertia', 'x-inertia-location', 'location'] as $name) {
            if ($built->headers->has($name)) {
                $response->getHeaders()->set($name, (string)$built->headers->get($name));
            }
        }
    }
}
