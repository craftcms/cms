<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\HttpTransportMiddleware;
use CraftCms\Cms\Mcp\RouteRegistrar;
use CraftCms\Cms\Mcp\ServerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Routing\Router;
use Mcp\Server;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
class McpController
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ServerFactory $servers,
        private readonly HttpTransportMiddleware $middleware,
    ) {}

    public function authenticated(): ResponseInterface
    {
        return $this->handle($this->servers->authenticated());
    }

    public function public(): ResponseInterface
    {
        return $this->handle($this->servers->public());
    }

    /**
     * Serves an endpoint both servers share. Requests with a bearer token go through the authenticated server's
     * middleware; the rest go to the public server.
     */
    public function shared(Request $request, Router $router, Container $container): Response
    {
        $authenticated = $request->bearerToken() !== null;
        $middleware = $authenticated ? RouteRegistrar::authenticatedMiddleware() : RouteRegistrar::publicMiddleware();

        return new Pipeline($container)
            ->send($request)
            ->through($router->resolveMiddleware($middleware))
            ->then(fn (Request $request): Response => Router::toResponse($request, $authenticated ? $this->authenticated() : $this->public()));
    }

    /**
     * Answers CORS preflight requests for both servers without authentication or collecting user instructions.
     * The transport responds before dispatching to the instruction-free protocol.
     */
    public function preflight(): ResponseInterface
    {
        return $this->handle($this->servers->preflight());
    }

    private function handle(Server $server): ResponseInterface
    {
        return $server->run(new StreamableHttpTransport(
            request: $this->request,
            middleware: $this->middleware->forRequest($this->request),
        ));
    }
}
