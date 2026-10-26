<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Enums\ComponentKind;
use CraftCms\Cms\Mcp\Schema\SettingsSchema;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * Describes the extendable component types that configuration accepts, such as field types and workflow stages.
 *
 * @since 6.0.0
 */
readonly class ComponentTypes
{
    public function __construct(
        private FieldTypes $fieldTypes,
        private Fields $fields,
        private WorkflowStageTypes $workflowStageTypes,
        private AssetTransformDrivers $assetTransformDrivers,
        private SettingsSchema $settingsSchema,
    ) {}

    /** @return array{kind: string, usage: string, types: list<array{type: string, name: string}>} */
    #[McpTool(
        name: 'component-types.list',
        description: 'Lists the available types of an extendable component, such as field types, workflow stage types, or Asset Transform drivers, including those plugins add. Call component-types.get for a type’s settings schema before configuring it.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(ComponentKind $kind): array
    {
        $types = match ($kind) {
            ComponentKind::Field => $this->fieldTypes->types()
                ->filter(static fn (string $class): bool => $class::isSelectable())
                ->map(static fn (string $class): array => ['type' => $class, 'name' => $class::displayName()]),
            ComponentKind::WorkflowStage => $this->workflowStageTypes->types()
                ->map(static fn (string $class): array => ['type' => $class, 'name' => $class::displayName()]),
            ComponentKind::AssetTransformDriver => collect($this->assetTransformDrivers->definitions())
                ->map(static fn ($definition, string $handle): array => ['type' => $handle, 'name' => $definition->name]),
        };

        return [
            'kind' => $kind->value,
            'usage' => $kind->usage(),
            'types' => $types->sortBy('name')->values()->all(),
        ];
    }

    /**
     * @param  string  $type  A type from component-types.list.
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'component-types.get',
        description: 'Returns a component type’s settings as JSON Schema, with each setting’s label, instructions, accepted values, and default. Settings it can’t describe in detail note the control panel control that sets them.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(ComponentKind $kind, string $type): array
    {
        $listed = array_column($this->list($kind)['types'], 'name', 'type');

        if (! isset($listed[$type])) {
            throw new ToolCallException("Unknown {$kind->value} type [$type]. Call component-types.list for the available types.");
        }

        $result = ['kind' => $kind->value, 'type' => $type, 'name' => $listed[$type], 'usage' => $kind->usage()];

        if ($kind === ComponentKind::AssetTransformDriver) {
            $definition = $this->assetTransformDrivers->definitions()[$type];

            return [
                ...$result,
                'settingsSchema' => $this->settingsSchema->fromUi($definition->settingsFields),
                'parametersSchema' => $this->settingsSchema->fromUi(array_values($definition->parameterFields)),
            ];
        }

        $component = $kind === ComponentKind::Field
            ? $this->fields->createField(['type' => $type])
            : ComponentHelper::createComponent(['type' => $type, 'settings' => []], WorkflowStageInterface::class);
        $ui = $component->settingsUi();

        return [
            ...$result,
            'settingsSchema' => $ui !== null
                ? $this->settingsSchema->fromUi($ui, $component->getSettings())
                : ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
        ];
    }
}
