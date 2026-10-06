<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;

/**
 * Lets the MCP SDK resolve autowireable capability classes that have not been explicitly bound.
 *
 * @since 6.0.0
 */
readonly class CapabilityContainer implements ContainerInterface
{
    public function __construct(private Container $app) {}

    public function get(string $id): mixed
    {
        return $this->app->make($id);
    }

    public function has(string $id): bool
    {
        return $this->app->bound($id) || class_exists($id);
    }
}
