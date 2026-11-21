<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Element\Eloquent\EloquentElement as EloquentElementType;
use CraftCms\Cms\Element\Eloquent\EloquentElements;
use CraftCms\Cms\Gql\Arguments\Elements\EloquentElement as EloquentElementArguments;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\Elements\EloquentElement as EloquentElementResolver;
use CraftCms\Cms\Gql\Types\Generators\EloquentElementType as EloquentElementTypeGenerator;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds queries for each registered Eloquent element type the schema can query, named after its
 * handle: `customers`, `customer` and `customerCount` for a `customer` type.
 *
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class EloquentElement extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        $queries = [];

        foreach (app(EloquentElements::class)->types() as $elementType) {
            if ($checkToken && ! GqlHelper::canSchema($elementType::gqlScope())) {
                continue;
            }

            $type = EloquentElementTypeGenerator::generateType($elementType);
            $arguments = EloquentElementArguments::forElementType($elementType);
            $names = self::queryNames($elementType);
            $plural = $elementType::pluralLowerDisplayName();

            $queries[$names['all']] = [
                'type' => Type::listOf($type),
                'args' => $arguments,
                'resolve' => EloquentElementResolver::class.'::resolve',
                'description' => "This query is used to query for $plural.",
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ];
            $queries[$names['count']] = [
                'type' => Type::nonNull(Type::int()),
                'args' => $arguments,
                'resolve' => EloquentElementResolver::class.'::resolveCount',
                'description' => "This query is used to return the number of $plural.",
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ];
            $queries[$names['one']] = [
                'type' => $type,
                'args' => $arguments,
                'resolve' => EloquentElementResolver::class.'::resolveOne',
                'description' => "This query is used to query for a single {$elementType::lowerDisplayName()}.",
                'complexity' => GqlHelper::relatedArgumentComplexity(),
            ];
        }

        return $queries;
    }

    /**
     * Returns the registered element type that a query belongs to.
     *
     * @return class-string<EloquentElementType<covariant Model>>|null
     */
    public static function elementTypeForQuery(string $name): ?string
    {
        return array_find(
            app(EloquentElements::class)->types(),
            fn (string $elementType) => in_array($name, self::queryNames($elementType), true),
        );
    }

    /**
     * @param  class-string<EloquentElementType<covariant Model>>  $elementType
     * @return array{all: string, one: string, count: string}
     */
    private static function queryNames(string $elementType): array
    {
        $handle = (string) $elementType::refHandle();
        $singular = Str::camel($handle);

        return [
            'all' => Str::camel(Str::plural($handle)),
            'one' => $singular,
            'count' => "{$singular}Count",
        ];
    }
}
