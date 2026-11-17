<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells clients where to discover an OAuth resource’s authorization server when a request isn’t authenticated.
 *
 * Use it with the resource’s handle, such as `AddOAuthChallenge::class.':mcp'`.
 *
 * @since 6.0.0
 */
readonly class AddOAuthChallenge
{
    public function __construct(private OAuthResources $resources) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === 401 && ($definition = $this->resources->get($resource))) {
            $response->headers->set('WWW-Authenticate', $definition->challenge());
        }

        return $response;
    }
}
