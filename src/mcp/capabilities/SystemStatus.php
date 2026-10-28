<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\config\GeneralConfig;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\services\ProjectConfig;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * The system status (“System Status” in General settings), which determines whether the front end is online.
 *
 * @since 5.12.0
 */
class SystemStatus
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private ProjectConfig $projectConfig,
    ) {
    }

    /** @return array{live: bool} */
    #[McpTool(
        name: 'system-status.get',
        description: 'Returns whether the system is live (online). When it is off, only users who can access the site while it’s offline can see the front end.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(): array
    {
        return ['live' => Craft::$app->getIsLive()];
    }

    /** @return array{live: bool} */
    #[McpTool(
        name: 'system-status.enable',
        description: 'Turns the system on, as the General settings do.',
        annotations: new ToolAnnotations(destructiveHint: false, idempotentHint: true),
    )]
    #[RequiresAdminChanges]
    public function enable(): array
    {
        return $this->set(true);
    }

    /** @return array{live: bool} */
    #[McpTool(
        name: 'system-status.disable',
        description: 'Turns the system off, as the General settings do. Visitors and users without access while the system is off are shown an offline page.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    #[RequiresAdminChanges]
    public function disable(): array
    {
        return $this->set(false);
    }

    /** @return array{live: bool} */
    private function set(bool $live): array
    {
        if (is_bool($this->generalConfig->isSystemLive)) {
            throw new ToolCallException('The system status is set by the isSystemLive config setting, so it can’t be changed here.');
        }

        $current = $this->projectConfig->get('system.live');

        if (is_string($current) && str_starts_with($current, '$')) {
            throw new ToolCallException("The system status is set by the $current environment variable, so it can’t be changed here.");
        }

        if ($this->projectConfig->readOnly) {
            throw new ToolCallException('Changing the system status is not allowed while the project config is in read-only mode.');
        }

        $this->projectConfig->set('system.live', $live, $live ? 'Turn the system on.' : 'Turn the system off.');

        return $this->get();
    }
}
