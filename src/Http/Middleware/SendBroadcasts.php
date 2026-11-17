<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Support\Json;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the messages queued with `session()->broadcastToJs()` that the response didn't deliver itself.
 *
 * Inertia pages share them as their `broadcasts` prop, and legacy pages flash them as JS. Any other response, such as
 * an action's JSON, lists them in an `X-Craft-Broadcast` header, which the control panel's HTTP clients relay. After a
 * redirect they're left for the next page.
 *
 * @internal
 *
 * @since 6.0.0
 */
class SendBroadcasts
{
    public const string HEADER = 'X-Craft-Broadcast';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof RedirectResponse || ! $request->hasSession()) {
            return $response;
        }

        $messages = $request->session()->getBroadcasts();

        if ($messages !== []) {
            // Escaped to ASCII, as header values must be
            $response->headers->set(self::HEADER, (string) Json::encode($messages, 0));
        }

        return $response;
    }
}
