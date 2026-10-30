<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use GraphQL\Type\Definition\Argument;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
abstract class ElementMutationArguments extends MutationArguments
{
    /** @return array<string, ArgumentConfig> */
    #[\Override]
    public static function getArguments(): array
    {
        $arguments = array_merge(parent::getArguments(), static::propertyArguments(Element::class, ['title', 'enabled']));

        if ($elementType = static::elementType()) {
            $arguments += static::propertyArguments($elementType, array_values(array_diff(
                array_keys(array_filter(ElementProperty::all($elementType), fn (ElementProperty $property): bool => $property->writable)),
                array_keys(ElementProperty::all(Element::class)),
            )));
        }

        return $arguments;
    }

    /**
     * Returns the element type whose properties should be added as arguments automatically.
     *
     * Every property that the element type declares with {@see ElementProperty} is added, except for properties that
     * the base {@see Element} declares.
     *
     * @return class-string<ElementInterface>|null
     */
    protected static function elementType(): ?string
    {
        return null;
    }
}
