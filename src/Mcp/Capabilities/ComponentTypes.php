<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use Closure;
use CraftCms\Cms\Component\Kinds\ComponentKind;
use CraftCms\Cms\Component\Kinds\ComponentKinds;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * Describes the extendable component types that configuration accepts, such as field types and workflow stages.
 *
 * @since 6.0.0
 */
readonly class ComponentTypes
{
    public function __construct(private ComponentKinds $kinds) {}

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'component-types.list',
        description: 'Lists the available types of an extendable component kind, such as field types, workflow stage types, asset transform drivers, field layout elements, or condition rules, including those plugins add. Omit kind to list the kinds. Call component-types.get for a type’s settings schema before configuring it.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(
        #[Schema(description: 'A kind handle, such as field. Omit to list the kinds.')]
        ?string $kind = null,
        #[Schema(description: 'Required by kinds that list a scope, such as the element type for field-layout-element.')]
        ?string $scope = null,
    ): array {
        if ($kind === null) {
            return ['kinds' => array_map(fn (string $handle): array => $this->summarize($this->kind($handle)), $this->kinds->handles())];
        }

        $componentKind = $this->kind($kind);

        return [
            'kind' => $kind,
            'usage' => $componentKind->usage(),
            'types' => $this->call(fn (): array => $componentKind->types($this->scope($componentKind, $scope))),
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
    public function get(
        #[Schema(description: 'A kind handle from component-types.list, such as field.')]
        string $kind,
        string $type,
        #[Schema(description: 'Required by kinds that list a scope, such as the element type for field-layout-element.')]
        ?string $scope = null,
    ): array {
        $componentKind = $this->kind($kind);
        $scope = $this->scope($componentKind, $scope);
        $listed = array_column($this->call(fn (): array => $componentKind->types($scope)), null, 'type');

        if (! isset($listed[$type])) {
            throw new ToolCallException("Unknown $kind type [$type]. Call component-types.list for the available types.");
        }

        return [
            'kind' => $kind,
            ...$listed[$type],
            'usage' => $componentKind->usage(),
            ...$this->withConditionHints($this->call(fn (): array => $componentKind->describe($type, $scope))),
        ];
    }

    /**
     * Runs a component kind method, reporting invalid input as a tool error.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function call(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (InvalidArgumentException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Points condition settings to the condition-rule kind that lists their rules.
     *
     * @param  array<array-key, mixed>  $schema
     * @return array<array-key, mixed>
     */
    private function withConditionHints(array $schema): array
    {
        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = $this->withConditionHints($value);
            }
        }

        $conditionClass = $schema['x-craft-condition-class'] ?? null;

        if (is_string($conditionClass)) {
            unset($schema['x-craft-condition-class']);
            $hint = "component-types.list with kind: condition-rule and scope: $conditionClass lists the rule types it accepts.";
            $schema['description'] = isset($schema['description']) ? "{$schema['description']} $hint" : $hint;
        }

        return $schema;
    }

    private function kind(string $handle): ComponentKind
    {
        return $this->kinds->get($handle)
            ?? throw new ToolCallException("Unknown component kind [$handle]. Available kinds: ".implode(', ', $this->kinds->handles()).'.');
    }

    private function scope(ComponentKind $kind, ?string $scope): ?string
    {
        $description = $kind->scope();

        if ($description !== null && ($scope === null || $scope === '')) {
            throw new ToolCallException("Provide scope: $description");
        }

        return $description !== null ? $scope : null;
    }

    /** @return array{kind: string, description: string, scope?: string} */
    private function summarize(ComponentKind $kind): array
    {
        return [
            'kind' => $kind::handle(),
            'description' => $kind->description(),
            ...($kind->scope() !== null ? ['scope' => $kind->scope()] : []),
        ];
    }
}
