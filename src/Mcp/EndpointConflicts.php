<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Illuminate\Routing\Router;

/**
 * Finds routes that an MCP endpoint would conflict with.
 *
 * @since 6.0.0
 */
readonly class EndpointConflicts
{
    private const array Methods = ['GET', 'HEAD', 'DELETE', 'POST', 'OPTIONS'];

    public function __construct(private Router $router) {}

    public function find(string $endpoint): ?string
    {
        $path = trim($endpoint, '/');

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if (
                $route->isFallback
                || ($route->defaults['mcp'] ?? false) === true
                || $route->uri() !== $path
                || $route->getDomain() !== null
            ) {
                continue;
            }

            $methods = array_values(array_intersect(self::Methods, $route->methods()));

            if ($methods !== []) {
                return sprintf(
                    'The MCP endpoint [%s] conflicts with an existing [%s] route.',
                    $endpoint,
                    implode(', ', $methods),
                );
            }
        }

        return null;
    }
}
