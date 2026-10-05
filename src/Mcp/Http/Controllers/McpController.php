<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\HttpTransportMiddleware;
use CraftCms\Cms\Mcp\ServerFactory;
use Mcp\Server\Transport\StatelessHttpTransport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public function admin(): ResponseInterface
    {
        return new StatelessHttpTransport(
            protocol: $this->servers->admin(),
            middleware: $this->middleware->forRequest($this->request),
        )->handle($this->request);
    }

    public function public(): ResponseInterface
    {
        return new StatelessHttpTransport(
            protocol: $this->servers->public(),
            middleware: $this->middleware->forRequest($this->request),
        )->handle($this->request);
    }
}
