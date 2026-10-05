<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\BaseOptionsField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Gql\Types\DateTime as DateTimeType;
use CraftCms\Cms\Gql\Types\Money as MoneyType;
use CraftCms\Cms\Gql\Types\Number as NumberType;
use CraftCms\Cms\Mcp\Contracts\ProvidesInputSchema;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use Throwable;

/**
 * @since 6.0.0
 */
readonly class CustomFieldSchema
{
    /** @return array<string, mixed> */
    public function forElement(ElementInterface $element): array
    {
        $layout = $element->getFieldLayout();

        if (! $layout) {
            return $this->objectSchema([]);
        }

        $properties = [];
        $required = [];
        $layoutFields = $layout->getFields(
            fn (BaseField $layoutField): bool => $layoutField instanceof CustomField
                && $layoutField->editable($element),
        );

        foreach ($layoutFields as $layoutField) {
            if (! $layoutField instanceof CustomField) {
                continue;
            }

            $field = $layoutField->getField();
            $handle = $field->handle;

            if (! $handle) {
                continue;
            }

            $properties[$handle] = $this->fieldSchema($field, $layoutField->required);

            if ($layoutField->required) {
                $required[] = $handle;
            }
        }

        return $this->objectSchema($properties, $required);
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function objectSchema(array $properties, array $required = []): array
    {
        return array_filter([
            'type' => 'object',
            'description' => 'Custom field values keyed by field handle.',
            'properties' => $properties ?: null,
            'required' => $required ?: null,
            'additionalProperties' => false,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    private function fieldSchema(FieldInterface $field, bool $required): array
    {
        $schema = match (true) {
            $field instanceof ProvidesInputSchema => $field->getMcpInputSchema(),
            $field instanceof Json => [],
            $field instanceof BaseOptionsField => $this->optionsSchema($field),
            $field instanceof Email => ['type' => 'string', 'format' => 'email'],
            default => $this->inferredSchema($field),
        };

        $schema = $required ? $this->withoutNull($schema) : $this->withNull($schema);
        $schema['title'] = $field->name;

        if ($field->instructions !== null && $field->instructions !== '') {
            $schema['description'] = $field->instructions;
        }

        return array_filter($schema, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /** @return array<string, mixed> */
    private function optionsSchema(BaseOptionsField $field): array
    {
        $values = array_values(array_map(
            static fn (array $option): string => (string) $option['value'],
            array_filter($field->options, static fn (array $option): bool => isset($option['value'])),
        ));
        $itemSchema = ['type' => 'string'];

        if (! $field->customOptions && $values !== []) {
            $itemSchema['enum'] = $values;
        }

        return $field->getIsMultiOptionsField()
            ? ['type' => 'array', 'items' => $itemSchema]
            : $itemSchema;
    }

    /** @return array<string, mixed> */
    private function inferredSchema(FieldInterface $field): array
    {
        try {
            return $this->gqlSchema($field->getContentGqlMutationArgumentType());
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  Type|array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function gqlSchema(Type|array $definition): array
    {
        $type = is_array($definition) ? $definition['type'] : $definition;
        $schema = $this->typeSchema($type);

        if (is_array($definition) && isset($definition['description'])) {
            $schema['description'] = $definition['description'];
        }

        return $schema;
    }

    /**
     * @param  list<string>  $visited
     * @return array<string, mixed>
     */
    private function typeSchema(Type $type, array $visited = []): array
    {
        if ($type instanceof NonNull) {
            return $this->withoutNull($this->typeSchema($type->getWrappedType(), $visited));
        }

        if ($type instanceof ListOfType) {
            return $this->withNull([
                'type' => 'array',
                'items' => $this->typeSchema($type->getWrappedType(), $visited),
            ]);
        }

        if ($type instanceof InputObjectType) {
            if (in_array($type->name, $visited, true)) {
                return ['type' => ['object', 'null'], 'additionalProperties' => true];
            }

            $properties = [];
            $required = [];

            foreach ($type->getFields() as $field) {
                $properties[$field->name] = array_filter([
                    ...$this->typeSchema($field->getType(), [...$visited, $type->name]),
                    'description' => $field->description,
                ], static fn (mixed $value): bool => $value !== null && $value !== '');

                if ($field->isRequired()) {
                    $required[] = $field->name;
                }
            }

            return $this->withNull(array_filter([
                'type' => 'object',
                'properties' => $properties ?: null,
                'required' => $required ?: null,
                'additionalProperties' => false,
            ], static fn (mixed $value): bool => $value !== null));
        }

        if ($type instanceof EnumType) {
            return $this->withNull([
                'type' => 'string',
                'enum' => array_map(static fn ($value): string => $value->name, $type->getValues()),
            ]);
        }

        return $this->withNull($this->scalarSchema($type));
    }

    /** @return array<string, mixed> */
    private function scalarSchema(Type $type): array
    {
        return match (true) {
            $type instanceof BooleanType => ['type' => 'boolean'],
            $type instanceof IntType => ['type' => 'integer'],
            $type instanceof FloatType, $type instanceof NumberType, $type instanceof MoneyType => ['type' => 'number'],
            $type instanceof IDType, $type instanceof StringType => ['type' => 'string'],
            $type instanceof DateTimeType => ['type' => 'string', 'format' => 'date-time'],
            $type instanceof ScalarType => [],
            $type instanceof WrappingType => $this->typeSchema($type->getWrappedType()),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function withNull(array $schema): array
    {
        if (! isset($schema['type'])) {
            return $schema;
        }

        $types = (array) $schema['type'];
        $schema['type'] = array_values(array_unique([...$types, 'null']));

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function withoutNull(array $schema): array
    {
        if (! isset($schema['type'])) {
            return $schema;
        }

        $types = array_values(array_diff((array) $schema['type'], ['null']));
        $schema['type'] = count($types) === 1 ? $types[0] : $types;

        return $schema;
    }
}
