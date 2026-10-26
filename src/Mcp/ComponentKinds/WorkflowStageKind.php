<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ComponentKinds;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Mcp\Schema\SettingsSchema;
use CraftCms\Cms\Workflow\WorkflowStageTypes;

/**
 * Workflow stage types.
 *
 * @since 6.0.0
 */
readonly class WorkflowStageKind extends TypeRegistryKind
{
    public function __construct(
        SettingsSchema $settingsSchema,
        private WorkflowStageTypes $workflowStageTypes,
    ) {
        parent::__construct($settingsSchema);
    }

    public static function handle(): string
    {
        return 'workflow-stage';
    }

    public function description(): string
    {
        return 'Workflow stage types.';
    }

    public function usage(): string
    {
        return 'configuration.* with type: workflows takes the class as each stage’s type and its settings as the stage’s settings.';
    }

    protected function registry(): TypeRegistry
    {
        return $this->workflowStageTypes;
    }
}
