<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Update\Maintenance;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class MaintenanceMode
{
    public function __construct(private Maintenance $maintenance) {}

    /** @return array{enabled: bool} */
    #[McpTool(
        name: 'maintenance-mode.get',
        description: 'Returns whether the system is in maintenance mode.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(): array
    {
        return ['enabled' => $this->maintenance->isEnabled()];
    }

    /** @return array{enabled: true} */
    #[McpTool(
        name: 'maintenance-mode.enable',
        description: 'Puts the system into maintenance mode, as the General settings do. Visitors and users without access while the system is off are shown a maintenance page.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    #[RequiresAdmin]
    public function enable(): array
    {
        $this->maintenance->set(true, $this->maintenance->retryDuration());

        return ['enabled' => true];
    }

    /** @return array{enabled: false} */
    #[McpTool(
        name: 'maintenance-mode.disable',
        description: 'Takes the system out of maintenance mode.',
        annotations: new ToolAnnotations(destructiveHint: false, idempotentHint: true),
    )]
    #[RequiresAdmin]
    public function disable(): array
    {
        $this->maintenance->set(false, null);

        return ['enabled' => false];
    }
}
