<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use Closure;
use CraftCms\Cms\Http\Middleware\AuthenticateCraftSession;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Routing\Router;
use Livewire\Mechanisms\HandleComponents\Checksum;

/**
 * Gives Livewire update requests for components rendered on Craft routes the same middleware as those
 * pages: Craft’s auth guard, site, locale, maintenance handling, and so on.
 *
 * Livewire has one update endpoint for every component in the app, so this only applies Craft’s
 * middleware when the component was rendered on a route under the `craft` middleware, as recorded by
 * {@see SupportCraftSites}. Updates for the app’s own components pass through untouched.
 *
 * The update endpoint is at the root of the host, so the component’s site is also passed to Craft as
 * the `X-Craft-Site` header. Otherwise a component on a site with a base path, such as `/fr`, would be
 * updated on the primary site.
 *
 * @since 6.0.0
 */
readonly class ApplyCraftMiddleware
{
    public function __construct(
        private Container $container,
        private Router $router,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $siteId = $this->craftSiteId($request);

        if ($siteId === null) {
            return $next($request);
        }

        $request->attributes->set(SupportCraftSites::ATTRIBUTE, true);

        if (! $request->headers->has('X-Craft-Site')) {
            $request->headers->set('X-Craft-Site', (string) $siteId);
            // Livewire matches the page’s route again with a request built from the server params.
            $request->server->set('HTTP_X_CRAFT_SITE', (string) $siteId);
        }

        return new Pipeline($this->container)
            ->send($request)
            ->through($this->router->resolveMiddleware(['craft', AuthenticateCraftSession::class]))
            ->then($next);
    }

    /**
     * Returns the site the first component was rendered for, if it was rendered on a Craft route.
     *
     * The snapshot’s checksum is checked first, so a client can’t mark the app’s own components as Craft’s.
     * Livewire checks it again, and rejects the request, when it hydrates the component.
     */
    private function craftSiteId(Request $request): ?int
    {
        $snapshot = Json::decodeIfJson($request->input('components.0.snapshot'));

        if (! is_array($snapshot) || ! is_string($checksum = $snapshot['checksum'] ?? null)) {
            return null;
        }

        unset($snapshot['checksum']);

        if (! hash_equals(Checksum::generate($snapshot), $checksum)) {
            return null;
        }

        $siteId = $snapshot['memo'][SupportCraftSites::MEMO_KEY] ?? null;

        return is_int($siteId) ? $siteId : null;
    }
}
