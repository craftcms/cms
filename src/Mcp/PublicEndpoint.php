<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Illuminate\Routing\Router;

/**
 * @since 6.0.0
 */
readonly class PublicEndpoint
{
    private const array Methods = ['GET', 'HEAD', 'DELETE', 'POST', 'OPTIONS'];

    public function __construct(private Router $router) {}

    public function conflict(string $endpoint): ?string
    {
        $path = ltrim($endpoint, '/');

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if (
                $route->isFallback
                || ($route->defaults['publicMcp'] ?? false) === true
                || $route->uri() !== $path
                || $route->getDomain() !== null
            ) {
                continue;
            }

            $methods = array_values(array_intersect(self::Methods, $route->methods()));

            if ($methods !== []) {
                return sprintf(
                    'The public MCP endpoint [%s] conflicts with an existing [%s] route.',
                    $endpoint,
                    implode(', ', $methods),
                );
            }
        }

        return null;
    }
}
