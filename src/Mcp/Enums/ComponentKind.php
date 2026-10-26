<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Enums;

/**
 * Kinds of extendable components that MCP clients can choose and configure.
 *
 * @since 6.0.0
 */
enum ComponentKind: string
{
    case Field = 'field';
    case WorkflowStage = 'workflow-stage';
    case AssetTransformDriver = 'asset-transform-driver';

    /** Where clients use the component’s type and settings. */
    public function usage(): string
    {
        return match ($this) {
            self::Field => 'configuration.create with type: fields takes the class as attributes.type and its settings as attributes.settings.',
            self::WorkflowStage => 'configuration.* with type: workflows takes the class as each stage’s type and its settings as the stage’s settings.',
            self::AssetTransformDriver => 'configuration.* with type: asset-transformers takes the handle as driver and its settings as settings. Image transforms take parametersSchema values in parameters, keyed by the Asset Transformer’s UID.',
        };
    }
}
