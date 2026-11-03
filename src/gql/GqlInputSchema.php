<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql;

use craft\gql\types\DateTime as DateTimeType;
use craft\gql\types\Money as MoneyType;
use craft\gql\types\Number as NumberType;
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
use stdClass;

/**
 * Describes the values GraphQL input types accept as JSON schemas.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class GqlInputSchema
{
    /**
     * Returns the JSON schema of the values a GraphQL input type accepts.
     *
     * Input object types with an `isOneOf` config value accept exactly one of their fields.
     *
     * @param Type|array<string, mixed> $definition A type, or a field definition with a `type` and an optional `description`.
     * @return array<string, mixed>
     */
    public static function fromType(Type|array $definition): array
    {
        $type = is_array($definition) ? $definition['type'] : $definition;
        $schema = self::typeSchema($type);

        if (is_array($definition) && isset($definition['description'])) {
            $schema['description'] = $definition['description'];
        }

        return $schema;
    }

    /**
     * Returns a schema that also accepts `null`.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function withNull(array $schema): array
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
     * Returns a schema that doesn’t accept `null`.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function withoutNull(array $schema): array
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

    /**
     * @param list<string> $visited
     * @return array<string, mixed>
     */
    private static function typeSchema(Type $type, array $visited = []): array
    {
        if ($type instanceof NonNull) {
            return self::withoutNull(self::typeSchema($type->getWrappedType(), $visited));
        }

        if ($type instanceof ListOfType) {
            return self::withNull([
                'type' => 'array',
                'items' => self::typeSchema($type->getWrappedType(), $visited) ?: new stdClass(),
            ]);
        }

        if ($type instanceof InputObjectType) {
            if (in_array($type->name, $visited, true)) {
                return ['type' => ['object', 'null'], 'additionalProperties' => true];
            }

            $properties = [];
            $required = [];
            $oneOf = $type->config['isOneOf'] ?? false;

            foreach ($type->getFields() as $field) {
                $fieldSchema = self::typeSchema($field->getType(), [...$visited, $type->name]);
                $properties[$field->name] = array_filter([
                    ...$fieldSchema,
                    'description' => $field->description ?? $fieldSchema['description'] ?? null,
                ], static fn(mixed $value): bool => $value !== null && $value !== '') ?: new stdClass();

                if ($field->isRequired()) {
                    $required[] = $field->name;
                }
            }

            return self::withNull(array_filter([
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
            return self::withNull([
                'type' => 'string',
                'enum' => array_map(static fn($value): string => $value->name, $type->getValues()),
            ]);
        }

        return self::withNull(self::scalarSchema($type));
    }

    /**
     * @return array<string, mixed>
     */
    private static function scalarSchema(Type $type): array
    {
        return match (true) {
            $type instanceof BooleanType => ['type' => 'boolean'],
            $type instanceof IntType => ['type' => 'integer'],
            $type instanceof FloatType, $type instanceof NumberType, $type instanceof MoneyType => ['type' => 'number'],
            $type instanceof IDType => ['type' => ['string', 'integer']],
            $type instanceof StringType => ['type' => 'string'],
            $type instanceof DateTimeType => ['type' => 'string', 'format' => 'date-time'],
            $type instanceof ScalarType => [],
            $type instanceof WrappingType => self::typeSchema($type->getWrappedType()),
            default => [],
        };
    }
}
