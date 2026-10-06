<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use CraftCms\Cms\Mcp\OAuth\Metadata;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** @since 6.0.0 */
readonly class AddOAuthChallenge
{
    public function __construct(private Metadata $metadata) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 401) {
            return $response;
        }

        $response->headers->set('WWW-Authenticate', sprintf(
            'Bearer realm="MCP Server", resource_metadata="%s", scope="%s"',
            $this->metadata->resourceMetadataUrl(),
            Metadata::SCOPE,
        ));

        return $response;
    }
}
