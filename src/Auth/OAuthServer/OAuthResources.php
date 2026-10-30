<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer;

use Closure;
use CraftCms\Cms\Auth\OAuthServer\Http\Controllers\ApproveAuthorizationController;
use CraftCms\Cms\Auth\OAuthServer\Http\Controllers\AuthorizationController;
use CraftCms\Cms\Auth\OAuthServer\Http\Controllers\MetadataController;
use CraftCms\Cms\Auth\OAuthServer\Http\Controllers\RegisterClientController;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\AuthorizeCpRequests;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Http\Middleware\PreventStaleDatabaseReads;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;

/**
 * The APIs that OAuth clients can register for and request access to.
 *
 * Each API registers a factory that returns its {@see OAuthResource}, and its OAuth routes with {@see routes()}:
 *
 * ```php
 * use CraftCms\Cms\Auth\OAuthServer\OAuthResource;
 * use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
 *
 * public function boot(OAuthResources $resources): void
 * {
 *     $resources->register('my-plugin', fn () => new OAuthResource(
 *         handle: 'my-plugin',
 *         levels: [
 *             'my-plugin:read' => ['label' => 'Read only', 'description' => 'View widgets.'],
 *             'my-plugin:write' => ['label' => 'Read and write', 'description' => 'View and change widgets.'],
 *         ],
 *         resourceUrl: url('my-plugin/api'),
 *         authorizationRoute: 'my-plugin/auth',
 *         baseUrl: config('app.url'),
 *         routeName: 'my-plugin.oauth',
 *     ));
 *     $resources->routes('my-plugin');
 * }
 * ```
 *
 * Its scopes must also be registered with Passport, such as through a personal access token scope group.
 *
 * @since 6.0.0
 */
#[Singleton]
class OAuthResources
{
    /** @var array<string, Closure(): (OAuthResource|null)> */
    private array $factories = [];

    public function __construct(private readonly Router $router) {}

    /** @param Closure(): (OAuthResource|null) $factory */
    public function register(string $handle, Closure $factory): void
    {
        $this->factories[$handle] = $factory;
    }

    public function get(string $handle): ?OAuthResource
    {
        return isset($this->factories[$handle]) ? ($this->factories[$handle])() : null;
    }

    /** The resource whose OAuth routes are handling the request. */
    public function forRequest(Request $request): ?OAuthResource
    {
        $handle = $request->route()?->defaults['oauthResource'] ?? null;

        return is_string($handle) ? $this->get($handle) : null;
    }

    /**
     * Registers the resource’s discovery, client registration, and authorization routes. Tokens are issued by
     * Passport’s token endpoint.
     */
    public function routes(string $handle): void
    {
        $resource = $this->get($handle) ?? throw new InvalidArgumentException("OAuth resource [$handle] isn’t registered.");
        $cp = $this->isCpPath($resource->authorizationRoute);

        $this->router->get($this->wellKnownPath($resource->resourceMetadataUrl()), [MetadataController::class, 'resource'])
            ->middleware(EnsureInstalled::class)
            ->defaults('oauthResource', $handle)
            ->name("$resource->routeName.resource");
        $this->router->get($this->wellKnownPath($resource->authorizationServerMetadataUrl()), [MetadataController::class, 'authorizationServer'])
            ->middleware(EnsureInstalled::class)
            ->defaults('oauthResource', $handle)
            ->name("$resource->routeName.authorization-server");

        $this->router->prefix(trim($resource->authorizationRoute, '/').'/oauth')
            ->name("$resource->routeName.")
            ->group(function (Router $router) use ($cp, $handle): void {
                $router->post('register', RegisterClientController::class)
                    ->middleware([EnsureInstalled::class, PreventStaleDatabaseReads::class, 'throttle:60,1'])
                    ->defaults('oauthResource', $handle)
                    ->name('register');

                $router->middleware(['web', 'craft', ...($cp ? [HandleInertiaRequests::class] : []), 'throttle:60,1', AuthorizeCpRequests::class])
                    ->group(function (Router $router) use ($handle): void {
                        $router->get('authorize', AuthorizationController::class)
                            ->defaults('oauthResource', $handle)
                            ->name('authorize');

                        $router->middleware(['auth:'.Cms::config()->getAuthGuard()])->group(function (Router $router) use ($handle): void {
                            $router->post('authorize', [ApproveAuthorizationController::class, 'approve'])
                                ->defaults('oauthResource', $handle)
                                ->name('approve');
                            $router->delete('authorize', [DenyAuthorizationController::class, 'deny'])
                                ->defaults('oauthResource', $handle)
                                ->name('deny');
                        });
                    });
            });
    }

    public function isCpPath(string $path): bool
    {
        $cpTrigger = trim((string) Cms::config()->cpTrigger, '/');

        return $cpTrigger !== '' && str_starts_with(trim($path, '/').'/', $cpTrigger.'/');
    }

    /** The route path for a well-known URL. */
    private function wellKnownPath(string $url): string
    {
        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}
