<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ComponentKinds;

use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\Schema\SettingsSchema;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use Mcp\Exception\ToolCallException;

/**
 * The native fields, custom fields, and UI elements an element type’s field layouts accept.
 *
 * @since 6.0.0
 */
readonly class FieldLayoutElementKind implements ComponentKind
{
    public function __construct(
        private Fields $fields,
        private ElementQueryFactory $elementQueries,
        private SettingsSchema $settingsSchema,
    ) {}

    public static function handle(): string
    {
        return 'field-layout-element';
    }

    public function description(): string
    {
        return 'Field layout elements: native fields, custom fields, and UI elements, for an element type.';
    }

    public function usage(): string
    {
        return 'Field layout configs list elements in each tab’s elements. Start each from the type’s config, which identifies it, such as a native field’s attribute or a custom field’s fieldUid, and add settings from component-types.get. Keep an existing element’s uid to keep it the same element; new elements get one.';
    }

    public function scope(): ?string
    {
        return 'The element type handle or class whose field layouts the elements go in, such as entries.';
    }

    public function types(?string $scope): array
    {
        return collect($this->elements($scope))
            ->map(fn (FieldLayoutElement $element, string $id): array => [
                'type' => $id,
                'name' => $this->name($element, $id),
                'config' => $this->config($element, $id),
            ])
            ->values()
            ->all();
    }

    public function describe(string $type, ?string $scope): array
    {
        return ['settingsSchema' => $this->schema($this->elements($scope)[$type])];
    }

    /**
     * Returns the elements available to an element type’s field layouts, keyed by an identifier that tells apart
     * elements of the same class, such as native fields for different attributes.
     *
     * @return array<string, FieldLayoutElement>
     */
    private function elements(?string $scope): array
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
            $keyed[$this->elementId($element, $byClass[$element::class]->all())] = $element;
        }

        return $keyed;
    }

    /**
     * Identifies an element by its class, plus the values that set it apart from other elements of the same class.
     *
     * @param  list<FieldLayoutElement>  $siblings
     */
    private function elementId(FieldLayoutElement $element, array $siblings): string
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

    private function name(FieldLayoutElement $element, string $id): string
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
    private function config(FieldLayoutElement $element, string $id): array
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
    private function schema(FieldLayoutElement $element): array
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
