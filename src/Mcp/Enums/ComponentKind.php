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
    case FieldLayoutElement = 'field-layout-element';

    /** Where clients use the component’s type and settings. */
    public function usage(): string
    {
        return match ($this) {
            self::Field => 'configuration.create with type: fields takes the class as attributes.type and its settings as attributes.settings.',
            self::WorkflowStage => 'configuration.* with type: workflows takes the class as each stage’s type and its settings as the stage’s settings.',
            self::FieldLayoutElement => 'Field layout configs list elements in each tab’s elements. Start each from the type’s config, which identifies it, such as a native field’s attribute or a custom field’s fieldUid, and add settings from component-types.get. Keep an existing element’s uid to keep it the same element; new elements get one.',
            self::AssetTransformDriver => 'configuration.* with type: asset-transformers takes the handle as driver and its settings as settings. Image transforms take parametersSchema values in parameters, keyed by the Asset Transformer’s UID.',
        };
    }
}
