<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Field\Table as TableField;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Gql\Types\TableRow;
use CraftCms\Cms\Support\Facades\Gql;

class TableRowType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(mixed $context = null): string
    {
        /** @var TableField $context */
        return $context->handle.'_TableRow';
    }

    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new TableRow([
            'name' => $typeName,
            'fields' => function () use ($context, $typeName) {
                /** @var TableField $context */
                $contentFields = TableRow::prepareRowFieldDefinition($context->columns);

                return Gql::prepareFieldDefinitions($contentFields, $typeName);
            },
        ]));
    }
}
