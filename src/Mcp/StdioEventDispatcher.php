<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Activity\ActivityEventRecorder;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\Context;
use Mcp\Event\ErrorEvent;
use Mcp\Event\RequestEvent;
use Mcp\Event\ResponseEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Handles each stdio request in a fresh request scope, and saves project config changes before each response is sent.
 *
 * Over HTTP, each request gets its own scope, and changes are saved as it ends. The stdio server is one long-running
 * command, so without this, requests would share state, and changes would only be saved when it stops, with nothing
 * else seeing them until then.
 *
 * @since 6.0.0
 */
class StdioEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        if ($event instanceof RequestEvent) {
            app()->forgetScopedInstances();
            Element::forgetSources();
            Context::addHidden(ActivityEventRecorder::ContextOrigin, 'MCP');
            Context::addHidden(StdioTransport::ContextStdio, true);
        }

        if ($event instanceof ResponseEvent || $event instanceof ErrorEvent) {
            app(ProjectConfig::class)->flushChanges();
        }

        return $event;
    }
}
