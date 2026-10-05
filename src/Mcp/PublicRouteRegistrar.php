<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Http\Middleware\AddLogContext;
use CraftCms\Cms\Http\Middleware\EnsureInstalled;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use CraftCms\Cms\Mcp\Http\Controllers\McpController;
use CraftCms\Cms\Mcp\Public\Access;
use Illuminate\Routing\Router;

/**
 * @since 6.0.0
 */
readonly class PublicRouteRegistrar
{
    public function __construct(
        private Router $router,
        private Access $access,
        private PublicEndpoint $endpoint,
    ) {}

    public function register(): void
    {
        if (
            ! $this->access->enabled()
            || $this->access->route() === ''
            || $this->endpoint->conflict('/'.$this->access->route()) !== null
        ) {
            return;
        }

        $middleware = [
            EnsureInstalled::class,
            AddLogContext::class,
            ResolveSite::class,
            UseWriteConnection::class,
            'throttle:60,1',
        ];

        $this->router->options($this->access->route(), [McpController::class, 'public'])
            ->middleware($middleware)
            ->defaults('publicMcp', true);
        $this->router->post($this->access->route(), [McpController::class, 'public'])
            ->middleware($middleware)
            ->defaults('publicMcp', true)
            ->name('craft.mcp.public');
    }
}
