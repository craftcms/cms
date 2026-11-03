<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Mcp\Event\ErrorEvent;
use Mcp\Event\ResponseEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Saves project config changes before each stdio response is sent.
 *
 * Over HTTP, changes are saved as each request ends. The stdio server is one
 * long-running command, so without this they'd only be saved when it stops,
 * and nothing else would see them until then.
 *
 * @since 6.0.0
 */
class StdioEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        if ($event instanceof ResponseEvent || $event instanceof ErrorEvent) {
            app(ProjectConfig::class)->flushChanges();
        }

        return $event;
    }
}
