<?php

declare(strict_types=1);

namespace craft\mcp\schema;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\fields\BaseOptionsField;
use craft\fields\Email;
use craft\fields\Json;
use craft\fields\Matrix;
use craft\gql\types\DateTime as DateTimeType;
use craft\gql\types\Money as MoneyType;
use craft\gql\types\Number as NumberType;
use craft\mcp\contracts\ProvidesInputSchema;
use GraphQL\Error\UserError;
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
use Illuminate\Support\Arr;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Exception\ToolCallException;
use stdClass;
use Throwable;

/**
 * @since 5.12.0
 */
class CustomFieldSchema
{
    public function __construct(
        private readonly SchemaValidator $schemaValidator = new SchemaValidator(),
    ) {
    }

    /** @return array<string, mixed> */
    public function forElement(ElementInterface $element): array
    {
        $layout = $element->getFieldLayout();

        if (!$layout) {
            return $this->objectSchema([]);
        }

        $properties = [];
        $required = [];
        // The fields the user can see and edit in the element’s form, as its layout’s conditions allow.
        foreach ($layout->getEditableCustomFieldElements($element) as $layoutField) {
            $field = $layoutField->getField();
            $handle = $field->handle;

            if (!$handle) {
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
     * Validates custom field values against the schema {@see forElement()} describes, and converts them into the
     * format the fields accept.
     *
     * Schemas inferred from a field's GraphQL mutation argument type describe GraphQL input, such as Matrix entries
     * keyed by entry type handle, so their values go through the same normalizers the GraphQL API applies.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     * @throws ToolCallException if the values don’t match the schema.
     */
    public function prepareValues(ElementInterface $element, array $values): array
    {
        if ($values === []) {
            return $values;
        }

        $this->assertValid($element, $values);
        $layout = $element->getFieldLayout();

        if (!$layout) {
            return $values;
        }

        foreach ($values as $handle => $value) {
            $field = $layout->getFieldByHandle((string) $handle);

            if (!$field || !$this->usesInferredSchema($field)) {
                continue;
            }

            try {
                $values[$handle] = $this->normalizeGqlValue($field->getContentGqlMutationArgumentType(), $value);
            } catch (UserError $exception) {
                throw new ToolCallException(sprintf('%s: %s', $handle, $exception->getMessage()), previous: $exception);
            }

            if ($field instanceof Matrix && !$this->listsNewEntries($values[$handle])) {
                throw new ToolCallException(sprintf('%s: New entries must be listed in `sortOrder`, as `new:1`, `new:2`, etc. in the order they appear in `entries`, along with the IDs of any existing entries to keep.', $handle));
            }
        }

        return $values;
    }

    /**
     * Required fields aren’t enforced, so values can update some fields; saving the element validates the rest.
     *
     * @param  array<string, mixed>  $values
     */
    private function assertValid(ElementInterface $element, array $values): void
    {
        $schema = Arr::except($this->forElement($element), 'required');
        $errors = $this->schemaValidator->validateAgainstJsonSchema($this->validationData($values, $schema), $schema);

        if ($errors === []) {
            return;
        }

        $messages = array_map(
            static fn(array $error): string => (in_array($error['pointer'], ['', '/'], true) ? '' : "{$error['pointer']}: ") . rtrim($error['message'], '.'),
            array_slice($errors, 0, 3),
        );

        throw new ToolCallException(sprintf(
            'Invalid fields: %s. Call elements.field-schema for the accepted fields.',
            implode('; ', $messages),
        ));
    }

    /**
     * Converts associative and empty arrays the schema describes as objects into objects, as JSON decoding loses the
     * distinction between `{}` and `[]`.
     *
     * @param  array<string, mixed>  $schema
     */
    private function validationData(mixed $value, array $schema): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value) && ($value !== [] || !in_array('object', (array) ($schema['type'] ?? []), true))) {
            return array_map(fn(mixed $item): mixed => $this->validationData($item, (array) ($schema['items'] ?? [])), $value);
        }

        $object = new stdClass();

        foreach ($value as $key => $item) {
            $object->{$key} = $this->validationData($item, (array) ($schema['properties'][$key] ?? []));
        }

        return $object;
    }

    /**
     * Returns whether a normalized Matrix value lists all of its new entries in `sortOrder`.
     *
     * The GraphQL API saves new entries missing from `sortOrder` without adding them to the field, so MCP rejects them.
     */
    private function listsNewEntries(mixed $value): bool
    {
        if (!is_array($value) || isset($value['sortOrder']) || !is_array($value['entries'] ?? null)) {
            return true;
        }

        foreach (array_keys($value['entries']) as $id) {
            if (str_starts_with((string) $id, 'new:')) {
                return false;
            }
        }

        return true;
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
        ], static fn(mixed $value): bool => $value !== null);
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

        return array_filter($schema, static fn(mixed $value): bool => $value !== null && $value !== '');
    }

    private function usesInferredSchema(FieldInterface $field): bool
    {
        return !$field instanceof ProvidesInputSchema
            && !$field instanceof Json
            && !$field instanceof BaseOptionsField
            && !$field instanceof Email;
    }

    /** @return array<string, mixed> */
    private function optionsSchema(BaseOptionsField $field): array
    {
        $values = array_values(array_map(
            static fn(array $option): string => (string) $option['value'],
            array_filter($field->options, static fn(array $option): bool => isset($option['value'])),
        ));
        $itemSchema = ['type' => 'string'];

        if (!$field->customOptions && $values !== []) {
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
     * Applies the `normalizeValue` callbacks of a GraphQL input type and the input types nested within it.
     *
     * @param  Type|array<string, mixed>  $definition
     */
    private function normalizeGqlValue(Type|array $definition, mixed $value): mixed
    {
        $type = is_array($definition) ? $definition['type'] : $definition;
        $normalizer = is_array($definition) ? $definition['normalizeValue'] ?? null : null;

        if ($type instanceof NonNull) {
            $value = $this->normalizeGqlValue($type->getWrappedType(), $value);
        } elseif ($type instanceof ListOfType && is_array($value)) {
            $value = array_map(fn(mixed $item): mixed => $this->normalizeGqlValue($type->getWrappedType(), $item), $value);
        } elseif ($type instanceof InputObjectType && is_array($value)) {
            $fields = $type->getFields();

            foreach ($value as $name => $fieldValue) {
                if (isset($fields[$name])) {
                    $value[$name] = $this->normalizeGqlValue($fields[$name]->getType(), $fieldValue);
                }
            }

            $normalizer = $type->config['normalizeValue'] ?? null;
        }

        return is_callable($normalizer) ? $normalizer($value) : $value;
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
                'items' => $this->typeSchema($type->getWrappedType(), $visited) ?: new stdClass(),
            ]);
        }

        if ($type instanceof InputObjectType) {
            if (in_array($type->name, $visited, true)) {
                return ['type' => ['object', 'null'], 'additionalProperties' => true];
            }

            $properties = [];
            $required = [];

            // GraphQL input objects that accept exactly one of their fields
            $oneOf = $type->config['isOneOf'] ?? false;

            foreach ($type->getFields() as $field) {
                $fieldSchema = $this->typeSchema($field->getType(), [...$visited, $type->name]);
                $properties[$field->name] = array_filter([
                    ...$fieldSchema,
                    'description' => $field->description ?? $fieldSchema['description'] ?? null,
                ], static fn(mixed $value): bool => $value !== null && $value !== '') ?: new stdClass();

                if ($field->isRequired()) {
                    $required[] = $field->name;
                }
            }

            return $this->withNull(array_filter([
                'type' => 'object',
                'description' => $type->description,
                'properties' => $properties ?: null,
                'required' => $required ?: null,
                'minProperties' => $oneOf ? 1 : null,
                'maxProperties' => $oneOf ? 1 : null,
                'additionalProperties' => false,
            ], static fn(mixed $value): bool => $value !== null && $value !== ''));
        }

        if ($type instanceof EnumType) {
            return $this->withNull([
                'type' => 'string',
                'enum' => array_map(static fn($value): string => $value->name, $type->getValues()),
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
            $type instanceof IDType => ['type' => ['string', 'integer']],
            $type instanceof StringType => ['type' => 'string'],
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
        if (!isset($schema['type'])) {
            return $schema;
        }

        $types = (array) $schema['type'];
        $schema['type'] = array_values(array_unique([...$types, 'null']));

        if (isset($schema['enum']) && !in_array(null, $schema['enum'], true)) {
            $schema['enum'][] = null;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function withoutNull(array $schema): array
    {
        if (!isset($schema['type'])) {
            return $schema;
        }

        $types = array_values(array_diff((array) $schema['type'], ['null']));
        $schema['type'] = count($types) === 1 ? $types[0] : $types;

        if (isset($schema['enum'])) {
            $schema['enum'] = array_values(array_filter($schema['enum'], static fn(mixed $value): bool => $value !== null));
        }

        return $schema;
    }
}
