<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces;

use CraftCms\Cms\Element\Element as ElementElement;
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
        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            ...static::propertyFields(ElementElement::class, ['lft', 'rgt', 'level', 'root', 'structureId']),
        ]), self::getName());
    }

    #[Override]
    public static function getName(): string
    {
        return 'StructureInterface';
    }
}
