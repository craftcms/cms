<?php

declare(strict_types=1);

namespace craft\mcp\schema;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\InputSchemaFieldInterface;
use craft\fields\Date;
use craft\fields\Json;
use craft\fields\Matrix;
use craft\fields\Time;
use craft\gql\GqlInputSchema;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
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

            if (!$field) {
                continue;
            }

            if (!$this->usesInferredSchema($field)) {
                // Date and time schemas accept any string, so a value those fields can’t parse is rejected rather than
                // clearing the field. Other fields can read a value as empty on purpose, such as a blank option.
                if (($field instanceof Date || $field instanceof Time) && $value !== null && $value !== '' && $field->normalizeValue($value, $element) === null) {
                    throw new ToolCallException(sprintf('%s: The value couldn’t be parsed.', $handle));
                }

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
        // A field whose schema can’t be built accepts anything, rather than breaking the element’s schema.
        try {
            $schema = match (true) {
                $field instanceof InputSchemaFieldInterface => $field->getInputSchema(),
                $field instanceof Json => [],
                default => GqlInputSchema::fromType($field->getContentGqlMutationArgumentType()),
            };
        } catch (Throwable) {
            $schema = [];
        }

        $schema = $required ? GqlInputSchema::withoutNull($schema) : GqlInputSchema::withNull($schema);
        $schema['title'] = $field->name;

        // The instructions are added to the schema’s own description, which can say what format a value takes.
        if ($field->instructions !== null && $field->instructions !== '') {
            $schema['description'] = isset($schema['description']) && $schema['description'] !== ''
                ? "{$schema['description']}\n\n{$field->instructions}"
                : $field->instructions;
        }

        return array_filter($schema, static fn(mixed $value): bool => $value !== null && $value !== '');
    }

    private function usesInferredSchema(FieldInterface $field): bool
    {
        return !$field instanceof InputSchemaFieldInterface && !$field instanceof Json;
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
}
