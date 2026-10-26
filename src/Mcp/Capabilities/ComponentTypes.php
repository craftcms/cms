<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\Enums\ComponentKind;
use CraftCms\Cms\Mcp\Schema\SettingsSchema;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use CraftCms\Cms\Workflow\Contracts\WorkflowStageInterface;
use CraftCms\Cms\Workflow\WorkflowStageTypes;
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
    private const string ScopeDescription = 'Required for field-layout-element: the element type handle or class whose field layouts the elements go in, such as entries.';

    public function __construct(
        private FieldTypes $fieldTypes,
        private Fields $fields,
        private WorkflowStageTypes $workflowStageTypes,
        private AssetTransformDrivers $assetTransformDrivers,
        private SettingsSchema $settingsSchema,
        private ElementQueryFactory $elementQueries,
    ) {}

    /** @return array{kind: string, usage: string, types: list<array{type: string, name: string, config?: array<string, mixed>}>} */
    #[McpTool(
        name: 'component-types.list',
        description: 'Lists the available types of an extendable component, such as field types, workflow stage types, Asset Transform drivers, or field layout elements, including those plugins add. Call component-types.get for a type’s settings schema before configuring it.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(
        ComponentKind $kind,
        #[Schema(description: self::ScopeDescription)]
        ?string $scope = null,
    ): array {
        $types = match ($kind) {
            ComponentKind::Field => $this->fieldTypes->types()
                ->filter(static fn (string $class): bool => $class::isSelectable())
                ->map(static fn (string $class): array => ['type' => $class, 'name' => $class::displayName()])
                ->sortBy('name'),
            ComponentKind::WorkflowStage => $this->workflowStageTypes->types()
                ->map(static fn (string $class): array => ['type' => $class, 'name' => $class::displayName()])
                ->sortBy('name'),
            ComponentKind::AssetTransformDriver => collect($this->assetTransformDrivers->definitions())
                ->map(static fn ($definition, string $handle): array => ['type' => $handle, 'name' => $definition->name])
                ->sortBy('name'),
            ComponentKind::FieldLayoutElement => collect($this->layoutElements($scope))
                ->map(fn (FieldLayoutElement $element, string $id): array => [
                    'type' => $id,
                    'name' => $this->layoutElementName($element, $id),
                    'config' => $this->layoutElementConfig($element, $id),
                ]),
        };

        return [
            'kind' => $kind->value,
            'usage' => $kind->usage(),
            'types' => $types->values()->all(),
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
        ComponentKind $kind,
        string $type,
        #[Schema(description: self::ScopeDescription)]
        ?string $scope = null,
    ): array {
        $listed = array_column($this->list($kind, $scope)['types'], null, 'type');

        if (! isset($listed[$type])) {
            throw new ToolCallException("Unknown {$kind->value} type [$type]. Call component-types.list for the available types.");
        }

        $result = ['kind' => $kind->value, ...$listed[$type], 'usage' => $kind->usage()];

        if ($kind === ComponentKind::AssetTransformDriver) {
            $definition = $this->assetTransformDrivers->definitions()[$type];

            return [
                ...$result,
                'settingsSchema' => $this->settingsSchema->fromUi($definition->settingsFields),
                'parametersSchema' => $this->settingsSchema->fromUi(array_values($definition->parameterFields)),
            ];
        }

        if ($kind === ComponentKind::FieldLayoutElement) {
            return [...$result, 'settingsSchema' => $this->layoutElementSchema($this->layoutElements($scope)[$type])];
        }

        $component = $kind === ComponentKind::Field
            ? $this->fields->createField(['type' => $type])
            : ComponentHelper::createComponent(['type' => $type, 'settings' => []], WorkflowStageInterface::class);

        return [...$result, 'settingsSchema' => $this->componentSchema($component)];
    }

    /** @return array<string, mixed> */
    private function componentSchema(ConfigurableComponentInterface $component): array
    {
        $ui = $component->settingsUi();

        return $ui !== null
            ? $this->settingsSchema->fromUi($ui, $component->getSettings())
            : ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false];
    }

    /**
     * Returns the elements available to an element type’s field layouts, keyed by an identifier that tells apart
     * elements of the same class, such as native fields for different attributes.
     *
     * @return array<string, FieldLayoutElement>
     */
    private function layoutElements(?string $scope): array
    {
        if ($scope === null) {
            throw new ToolCallException('Provide scope: the element type whose field layouts the elements go in, such as entries.');
        }

        $layout = $this->fields->createLayout(['type' => $this->elementQueries->resolve($scope)]);
        $customField = new CustomField($this->fields->createField(['type' => PlainText::class, 'uid' => Str::uuid()->toString()]));
        $elements = [...$layout->getAvailableNativeFields(), $customField, ...$layout->getAvailableUiElements()];
        $byClass = collect($elements)->groupBy(static fn (FieldLayoutElement $element): string => $element::class);
        $keyed = [];

        foreach ($elements as $element) {
            $element->setLayout($layout);
            $keyed[$this->layoutElementId($element, $byClass[$element::class]->all())] = $element;
        }

        return $keyed;
    }

    /**
     * Identifies an element by its class, plus the values that set it apart from other elements of the same class.
     *
     * @param  list<FieldLayoutElement>  $siblings
     */
    private function layoutElementId(FieldLayoutElement $element, array $siblings): string
    {
        $distinct = $this->distinctValues($element, $siblings);

        return $distinct === []
            ? $element::class
            : sprintf('%s(%s)', $element::class, implode(', ', array_map(static fn (string $key, mixed $value): string => "$key: $value", array_keys($distinct), $distinct)));
    }

    /**
     * @param  list<FieldLayoutElement>  $siblings
     * @return array<string, scalar>
     */
    private function distinctValues(FieldLayoutElement $element, array $siblings): array
    {
        if ($element instanceof BaseNativeField) {
            return ['attribute' => $element->attribute()];
        }

        if (count($siblings) < 2) {
            return [];
        }

        $values = $element->toArray();
        $others = array_map(static fn (FieldLayoutElement $sibling): array => $sibling->toArray(), array_filter($siblings, static fn (FieldLayoutElement $sibling): bool => $sibling !== $element));

        return array_filter(
            $values,
            static fn (mixed $value, string $key): bool => is_scalar($value) && array_all($others, static fn (array $other): bool => ($other[$key] ?? null) !== $value),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function layoutElementName(FieldLayoutElement $element, string $id): string
    {
        $name = Str::headline(class_basename($element));
        $suffix = Str::between($id, '(', ')');

        return $suffix !== $id ? "$name ($suffix)" : $name;
    }

    /**
     * The config to add an element to a field layout tab with, before its settings.
     *
     * @return array<string, mixed>
     */
    private function layoutElementConfig(FieldLayoutElement $element, string $id): array
    {
        $config = ['type' => $element::class];

        if ($element instanceof BaseNativeField) {
            $config['attribute'] = $element->attribute();
        } elseif ($element instanceof CustomField) {
            $config['fieldUid'] = '<a field UID from configuration.list with type: fields>';
        } elseif (str_contains($id, '(')) {
            $config = [...$config, ...$this->distinctValuesFromId($element, $id)];
        }

        return $config;
    }

    /** @return array<string, mixed> */
    private function distinctValuesFromId(FieldLayoutElement $element, string $id): array
    {
        $values = $element->toArray();
        $keys = array_map(static fn (string $pair): string => trim(Str::before($pair, ':')), explode(',', Str::between($id, '(', ')')));

        return array_intersect_key($values, array_flip($keys));
    }

    /** @return array<string, mixed> */
    private function layoutElementSchema(FieldLayoutElement $element): array
    {
        $ui = $element->settingsUi();
        $schema = $ui !== null
            ? $this->settingsSchema->fromUi($ui, $element->toArray())
            : ['type' => 'object', 'properties' => [], 'additionalProperties' => true];
        $properties = (array) $schema['properties'];

        if ($element instanceof BaseField && $element->requirable() && ! $element->mandatory()) {
            $properties['required'] = ['title' => 'Required', 'type' => 'boolean', 'default' => false];
        }

        if ($element->hasCustomWidth()) {
            $properties['width'] = [
                'title' => 'Width',
                'description' => 'The percentage of the tab’s width the element takes up.',
                'default' => 100,
                'enum' => array_map(static fn (FieldWidth $width): int => $width->value, FieldWidth::cases()),
            ];
        }

        return [...$schema, 'properties' => $properties ?: (object) []];
    }
}
