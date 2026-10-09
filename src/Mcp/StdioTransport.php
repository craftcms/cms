<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Activity\ActivityEventRecorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Context;
use Mcp\Server\Transport\StdioTransport as BaseStdioTransport;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handles each stdio message in a fresh request scope, as the HTTP servers do for each request.
 *
 * @since 6.0.0
 */
class StdioTransport extends BaseStdioTransport
{
    /**
     * @param  resource  $input
     * @param  resource  $output
     */
    public function __construct(
        private readonly Application $app,
        $input = \STDIN,
        $output = \STDOUT,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($input, $output, $logger);
    }

    #[\Override]
    protected function handleMessage(string $payload, ?Uuid $sessionId): void
    {
        $this->app->forgetScopedInstances();
        Context::addHidden(ActivityEventRecorder::ContextOrigin, 'MCP');

        parent::handleMessage($payload, $sessionId);
    }
}
