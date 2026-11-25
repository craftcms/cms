<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\ConditionSchema;
use CraftCms\Cms\Support\Str;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * The rules conditions accept where they’re configured.
 *
 * @since 6.0.0
 */
readonly class Conditions
{
    public function __construct(
        private McpActor $actor,
        private ConditionSchema $conditions,
        private ElementQueryFactory $elementTypes,
        private Fields $fields,
    ) {}

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'condition_schema',
        description: 'Lists the rules a condition accepts where it’s configured, with each rule’s template, operators, and options. Contexts: element-sources for custom source conditions in element_sources_save (pass elementType); field-layout for the userCondition and elementCondition of field layout tabs and elements in configuration_* fieldLayout attributes (pass fieldLayout for an existing layout, or elementType for a new one, and condition); relation-selection for the settings.selectionCondition of relation fields such as Entries, Assets, and Users (pass field for an existing field, or fieldType for a new one). Saves reject rules that aren’t listed here.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function schema(
        #[Schema(enum: ['element-sources', 'field-layout', 'relation-selection'])]
        string $context,
        #[Schema(description: 'element-sources, and field-layout without fieldLayout: element type reference handle, such as entry or asset, its plural, or its PHP class.')]
        ?string $elementType = null,
        #[Schema(format: 'uuid', description: 'field-layout: the UID of the layout, as fieldLayout.uid in configuration_get. Its fields become available to element rules.')]
        ?string $fieldLayout = null,
        #[Schema(enum: ['element', 'user'], description: 'field-layout: element for elementCondition, which tests the element being edited, or user for userCondition, which tests the current user, admins included. Conditions only hide tabs and fields in the edit form; they don’t restrict permissions.')]
        string $condition = 'element',
        #[Schema(description: 'relation-selection: UID or handle of an existing relation field.')]
        ?string $field = null,
        #[Schema(description: 'relation-selection: relation field class for a field that doesn’t exist yet, such as CraftCms\\Cms\\Field\\Entries.')]
        ?string $fieldType = null,
    ): array {
        if (! $this->actor->user()->isAdmin()) {
            throw new ToolCallException('Admin access is required for condition schemas.');
        }

        $built = match ($context) {
            'element-sources' => $this->conditions->forElementSources($this->elementTypes->resolve($elementType ?? throw new ToolCallException('Provide elementType.'))),
            'field-layout' => $this->fieldLayoutCondition($fieldLayout, $elementType, $condition),
            'relation-selection' => $this->conditions->forRelationSelection($this->relationField($field, $fieldType)),
            default => throw new ToolCallException('context must be element-sources, field-layout, or relation-selection.'),
        };

        return ['context' => $context, ...$this->conditions->describe($built)];
    }

    private function fieldLayoutCondition(?string $layoutUid, ?string $elementType, string $kind): ConditionInterface
    {
        if (! in_array($kind, ['element', 'user'], true)) {
            throw new ToolCallException('condition must be element or user.');
        }

        if ($layoutUid !== null) {
            $layout = $this->fields->getLayoutByUid($layoutUid) ?? throw new ToolCallException("Field layout “{$layoutUid}” doesn’t exist.");

            if (! $layout->type) {
                throw new ToolCallException('The field layout has no element type.');
            }

            return $this->conditions->forFieldLayout($layout->type, $layout, $kind);
        }

        if ($elementType === null) {
            throw new ToolCallException('Provide fieldLayout, or elementType for a layout that doesn’t exist yet.');
        }

        return $this->conditions->forFieldLayout($this->elementTypes->resolve($elementType), null, $kind);
    }

    private function relationField(?string $field, ?string $fieldType): BaseRelationField
    {
        if (($field === null) === ($fieldType === null)) {
            throw new ToolCallException('Provide exactly one of: field, fieldType.');
        }

        if ($fieldType !== null) {
            if (! ComponentHelper::validateComponentClass($fieldType, BaseRelationField::class)) {
                throw new ToolCallException("“{$fieldType}” isn’t a relation field type.");
            }

            $resolved = $this->fields->createField(['type' => $fieldType]);
        } else {
            $resolved = Str::isUuid($field) ? $this->fields->getFieldByUid($field) : $this->fields->getFieldByHandle($field);
        }

        if (! $resolved instanceof BaseRelationField) {
            throw new ToolCallException("“{$field}” isn’t a relation field.");
        }

        return $resolved;
    }
}
