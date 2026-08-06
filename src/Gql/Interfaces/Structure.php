<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces;

use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Override;

/** @phpstan-import-type FieldDefinitionConfig from FieldDefinition */
abstract class Structure extends Element
{
    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'lft' => [
                'name' => 'lft',
                'type' => Type::int(),
                'description' => 'The element’s left position within its structure.',
            ],
            'rgt' => [
                'name' => 'rgt',
                'type' => Type::int(),
                'description' => 'The element’s right position within its structure.',
            ],
            'level' => [
                'name' => 'level',
                'type' => Type::int(),
                'description' => 'The element’s level within its structure',
            ],
            'root' => [
                'name' => 'root',
                'type' => Type::int(),
                'description' => 'The element’s structure’s root ID',
            ],
            'structureId' => [
                'name' => 'structureId',
                'type' => Type::int(),
                'description' => 'The element’s structure ID.',
            ],
        ]), self::getName());
    }

    #[Override]
    public static function getName(): string
    {
        return 'StructureInterface';
    }
}
