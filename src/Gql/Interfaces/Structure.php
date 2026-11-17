<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Element as ElementElement;
use CraftCms\Cms\Element\Enums\ElementCapability;
use CraftCms\Cms\Gql\Attributes\Gql as GqlAttribute;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
abstract class Structure extends Element
{
    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        // Element::getFieldDefinitions() is called without forwarding the element type, so the cached structure
        // interface fields don't include the fields of whichever element type asked first.
        return static::withElementTypeFields(Gql::prepareFieldDefinitions(array_merge(Element::getFieldDefinitions(), [
            ...static::propertyFields(ElementElement::class, array_keys(array_filter(
                ElementProperty::all(ElementElement::class),
                fn (ElementProperty $property): bool => $property->readable && $property->requires === ElementCapability::Structures && GqlAttribute::of($property) !== null,
            ))),
        ]), self::getName()));
    }

    #[Override]
    public static function getName(): string
    {
        return 'StructureInterface';
    }
}
