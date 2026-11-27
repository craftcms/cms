<?php

declare(strict_types=1);

namespace CraftCms\Cms\Livewire;

use Closure;
use CraftCms\Cms\Http\Middleware\AuthenticateCraftSession;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
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
 * middleware when the components were rendered on a route under the `craft` middleware, as recorded by
 * {@see SupportCraftSites}. Updates for the app’s own components pass through untouched. An update that
 * bundles components rendered for different Craft sites, previews, or the app’s own routes is rejected.
 *
 * The update endpoint is at the root of the host, so the components’ site is passed to Craft as the
 * `X-Craft-Site` header. Otherwise a component on a site with a base path, such as `/fr`, would be
 * updated on the primary site. Components rendered for a preview get the preview’s token back, so the
 * update is a preview request too.
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
        $context = $this->craftContext($request);

        if ($context === null) {
            return $next($request);
        }

        $request->attributes->set(SupportCraftSites::ATTRIBUTE, true);

        // The site and preview come from the checksummed snapshots, never from headers the client sent.
        // The server params are set too, as Livewire and HandleTokenRequest build requests from them.
        $this->setHeader($request, 'X-Craft-Site', (string) $context['siteId']);

        $middleware = ['craft', AuthenticateCraftSession::class];

        if ($context['preview'] !== null) {
            // HandleTokenRequest has already handled any token the client sent, so it must be the preview’s.
            $token = $request->getToken();
            abort_if($token !== null && $token !== $context['preview']['token'], 400, 'Invalid token');

            $this->setHeader($request, 'X-Craft-Preview-Token', $context['preview']['param']);

            if ($token === null) {
                $this->setHeader($request, HandleTokenRequest::TOKEN_HEADER, $context['preview']['token']);
                array_unshift($middleware, HandleTokenRequest::class);
            }
        }

        return new Pipeline($this->container)
            ->send($request)
            ->through($this->router->resolveMiddleware($middleware))
            ->then($next);
    }

    private function setHeader(Request $request, string $name, string $value): void
    {
        $request->headers->set($name, $value);
        $request->server->set('HTTP_'.strtoupper(str_replace('-', '_', $name)), $value);
    }

    /**
     * Returns the Craft site and preview the components were rendered for, or `null` if they were rendered
     * on the app’s own routes.
     *
     * Each snapshot’s checksum is checked first, so a client can’t mark the app’s own components as Craft’s.
     * Livewire checks them again, and rejects the request, when it hydrates the components.
     *
     * @return array{siteId: int, preview: array{token: string, param: string}|null}|null
     */
    private function craftContext(Request $request): ?array
    {
        $components = $request->input('components');

        if (! is_array($components) || $components === []) {
            return null;
        }

        $contexts = array_values(array_map(fn (mixed $component): ?array => $this->snapshotContext(is_array($component) ? $component['snapshot'] ?? null : null), $components));
        $context = $contexts[0];

        foreach ($contexts as $other) {
            abort_if($other !== $context, 400, 'Livewire components rendered for different Craft contexts can’t be updated together.');
        }

        return $context;
    }

    /**
     * @return array{siteId: int, preview: array{token: string, param: string}|null}|null
     */
    private function snapshotContext(mixed $snapshot): ?array
    {
        $snapshot = is_string($snapshot) ? Json::decodeIfJson($snapshot) : null;

        if (! is_array($snapshot) || ! is_string($checksum = $snapshot['checksum'] ?? null)) {
            return null;
        }

        unset($snapshot['checksum']);

        if (! hash_equals(Checksum::generate($snapshot), $checksum)) {
            return null;
        }

        $siteId = $snapshot['memo'][SupportCraftSites::MEMO_KEY] ?? null;

        if (! is_int($siteId)) {
            return null;
        }

        $preview = $snapshot['memo'][SupportCraftSites::PREVIEW_MEMO_KEY] ?? null;

        return [
            'siteId' => $siteId,
            'preview' => is_string($preview['token'] ?? null) && is_string($preview['param'] ?? null)
                ? ['token' => $preview['token'], 'param' => $preview['param']]
                : null,
        ];
    }
}
