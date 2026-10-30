<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementPropertyType;
use CraftCms\Cms\Gql\Types\DateTime;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;
use LogicException;

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
     * Returns arguments for properties that an element type declares with {@see ElementProperty}.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>  $names  Property names to return, in this order. Every name must be declared.
     * @return array<string, ArgumentConfig>
     */
    protected static function propertyArguments(string $elementType, array $names): array
    {
        $properties = ElementProperty::all($elementType);
        $arguments = [];

        foreach ($names as $name) {
            $property = $properties[$name] ?? throw new LogicException("[$elementType] does not declare the element property [$name].");
            $arguments[$name] = [
                'name' => $name,
                'type' => match ($property->type) {
                    ElementPropertyType::String => Type::string(),
                    ElementPropertyType::Float => Type::float(),
                    ElementPropertyType::Int => Type::int(),
                    ElementPropertyType::Id => Type::id(),
                    ElementPropertyType::IdList => Type::listOf(Type::id()),
                    ElementPropertyType::Boolean => Type::boolean(),
                    ElementPropertyType::DateTime => DateTime::getType(),
                },
                'description' => $property->descriptionFor($elementType),
            ];
        }

        return $arguments;
    }
}
