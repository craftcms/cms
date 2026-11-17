<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Gql\Attributes\Gql;
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
        return array_merge(parent::getArguments(), static::gqlPropertyArguments(static::elementType() ?? Element::class));
    }

    /**
     * Returns the element type whose properties should be added as arguments automatically.
     *
     * Every writable property that the element type declares with {@see ElementProperty} and doesn’t exclude
     * with {@see Gql} is added. Without an element type, only the base {@see Element}’s properties are added.
     *
     * @return class-string<ElementInterface>|null
     */
    protected static function elementType(): ?string
    {
        return null;
    }
}
