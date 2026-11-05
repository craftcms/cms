<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Gql\Attributes\Gql;
use CraftCms\Cms\Gql\ElementSchemaTypes;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
abstract class MutationArguments
{
    /** @return array<string, ArgumentConfig> */
    public static function getArguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
                'description' => 'Set the element’s ID.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::string(),
                'description' => 'Set the element’s UID.',
            ],
        ];
    }

    /**
     * Returns arguments for an element type’s writable properties that {@see Gql} doesn’t exclude, unless they opt
     * out of mutations.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, ArgumentConfig>
     */
    protected static function gqlPropertyArguments(string $elementType): array
    {
        $properties = array_filter(
            ElementProperty::writable($elementType),
            fn (ElementProperty $property): bool => (bool) Gql::of($property)?->write,
        );

        return static::propertyArguments($elementType, array_keys($properties));
    }

    /**
     * Returns arguments for writable properties that an element type declares with {@see ElementProperty}.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>  $names  Property names to return, in this order. Every name must be declared as writable.
     * @return array<string, ArgumentConfig>
     */
    protected static function propertyArguments(string $elementType, array $names): array
    {
        $arguments = [];

        foreach (ElementProperty::writable($elementType, $names) as $name => $property) {
            $arguments[$name] = [
                'name' => $name,
                'type' => ElementSchemaTypes::property($property->type, input: true),
                'description' => $property->descriptionFor($elementType),
            ];
        }

        return $arguments;
    }
}
