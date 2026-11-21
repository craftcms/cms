<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use BackedEnum;
use CraftCms\Cms\Element\Eloquent\EloquentElement;
use CraftCms\Cms\Element\Eloquent\EloquentElements;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Element as ElementInterface;
use CraftCms\Cms\Gql\Types\DateTime;
use CraftCms\Cms\Gql\Types\Elements\Element;
use CraftCms\Cms\Gql\Types\Number;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Json;
use DateTimeInterface;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Generates the GraphQL types of registered Eloquent element types.
 *
 * Each type has the element fields, a field per custom field in the element type's
 * layout, and a field per model attribute the model doesn't hide from serialization.
 *
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class EloquentElementType extends Generator implements GeneratorInterface
{
    /** @var array<class-string, array<string, array{type_name: string, type: string}>> */
    private static array $columns = [];

    public static function generateTypes(mixed $context = null): array
    {
        $types = [];

        foreach (app(EloquentElements::class)->types() as $elementType) {
            if (GqlHelper::canSchema($elementType::gqlScope())) {
                $type = static::generateType($elementType);
                $types[$type->name] = $type;
            }
        }

        return $types;
    }

    /** @param class-string<EloquentElement<covariant Model>> $elementType */
    public static function generateType(string $elementType): ObjectType
    {
        $typeName = $elementType::gqlTypeName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new Element([
            'name' => $typeName,
            'description' => "This is the type for {$elementType::pluralLowerDisplayName()}.",
            'fields' => function () use ($elementType, $typeName) {
                // The element’s model attributes shadow custom fields with the same handles, so those are left out
                $fields = array_merge(
                    ElementInterface::getFieldDefinitions(),
                    array_diff_key(self::getContentFields($elementType::resolveFieldLayout()), array_flip($elementType::modelAttributes())),
                );

                // Model attributes can’t shadow the element’s fields or custom fields.
                $fields += self::attributeFields($elementType);

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }

    /**
     * Returns a field for each model attribute the element exposes and the model serializes.
     *
     * @param  class-string<EloquentElement<covariant Model>>  $elementType
     * @return array<string, FieldDefinitionConfig>
     */
    private static function attributeFields(string $elementType): array
    {
        $model = $elementType::newModel();
        $visible = $model->getVisible();
        $columns = self::$columns[$elementType] ??= array_column(DB::getReadSchemaBuilder()->getColumns($model->getTable()), null, 'name');
        $fields = [];

        foreach ($elementType::listedModelAttributes() as $attribute) {
            if ($visible !== [] && ! in_array($attribute, $visible, true)) {
                continue;
            }

            $fields[$attribute] = [
                'name' => $attribute,
                'type' => self::attributeType($model, $attribute, $columns[$attribute] ?? null),
                'description' => "The {$elementType::lowerDisplayName()}’s `$attribute` model attribute.",
                'resolve' => fn (EloquentElement $source, array $arguments, mixed $context, ResolveInfo $resolveInfo) => GqlHelper::applyDirectives(
                    $source,
                    $resolveInfo,
                    self::attributeValue($source->getModel()->getAttribute($attribute)),
                ),
            ];
        }

        return $fields;
    }

    /**
     * Types an attribute by its cast, or by its column when it has none. Integers that can be too wide for GraphQL’s
     * 32-bit `Int`, from `timestamp` casts, `bigint` and unsigned `int` columns and SQLite’s 64-bit integer columns, are
     * typed as `Number`.
     *
     * @param  array{type_name: string, type: string}|null  $column
     */
    private static function attributeType(Model $model, string $attribute, ?array $column): Type
    {
        $cast = $model->getCasts()[$attribute] ?? null;
        $columnType = strtolower($column['type_name'] ?? '');
        $integerType = in_array($columnType, ['bigint', 'int8'], true)
            || (in_array($columnType, ['int', 'integer'], true) && (
                str_contains(strtolower($column['type'] ?? ''), 'unsigned')
                || $model->getConnection()->getDriverName() === 'sqlite'
            ))
            ? Number::getType()
            : Type::int();

        if ($cast !== null) {
            return match (strtolower(explode(':', $cast, 2)[0])) {
                'int', 'integer' => $integerType,
                'timestamp' => Number::getType(),
                'real', 'float', 'double' => Type::float(),
                'bool', 'boolean' => Type::boolean(),
                'date', 'datetime', 'immutable_date', 'immutable_datetime' => DateTime::getType(),
                default => Type::string(),
            };
        }

        return match ($columnType) {
            'integer', 'int', 'bigint', 'smallint', 'mediumint', 'tinyint', 'int2', 'int4', 'int8' => $integerType,
            'real', 'float', 'double', 'float4', 'float8' => Type::float(),
            'bool', 'boolean' => Type::boolean(),
            default => Type::string(),
        };
    }

    /**
     * Returns an attribute's value as its GraphQL type can serialize it: enums by their values, and arrays and
     * objects, such as those an `array` or `collection` cast returns, as JSON.
     */
    private static function attributeValue(mixed $value): mixed
    {
        return match (true) {
            $value === null, is_scalar($value), $value instanceof DateTimeInterface => $value,
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            default => Json::encode($value),
        };
    }
}
