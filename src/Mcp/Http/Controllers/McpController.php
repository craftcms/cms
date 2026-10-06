<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\HttpTransportMiddleware;
use CraftCms\Cms\Mcp\ServerFactory;
use Mcp\Server\Stateless\StatelessProtocol;
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
        return $this->handle($this->servers->admin());
    }

    public function public(): ResponseInterface
    {
        return $this->handle($this->servers->public());
    }

    /**
     * Answers CORS preflight requests for both servers without authentication or collecting user instructions.
     * The transport responds before dispatching to the instruction-free protocol.
     */
    public function preflight(): ResponseInterface
    {
        return $this->handle($this->servers->preflight());
    }

    private function handle(StatelessProtocol $protocol): ResponseInterface
    {
        return new StatelessHttpTransport(
            protocol: $protocol,
            middleware: $this->middleware->forRequest($this->request),
        )->handle($this->request);
    }
}
