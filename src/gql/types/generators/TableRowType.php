<?php
namespace craft\gql\types\generators;

use craft\fields\Table as TableField;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\types\DateTime;
use craft\gql\types\TableRow;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRowType
 */
class TableRowType implements BaseGenerator
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        /** @var TableField $context */
        $typeName = $context->handle . '_TableRow';

        $contentFields = [];

        foreach ($context->columns as $columnKey => $columnDefinition) {
            $cellType = in_array($columnDefinition['type'], ['date', 'time'], true) ? DateTime::getType() : Type::string();
            $contentFields[$columnKey] = $cellType;
            $contentFields[$columnDefinition['handle']] = $cellType;
        }

        // Generate a type for each entry type
        $tableRowType = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new TableRow([
            'name' => $typeName,
            'fields' => function () use ($contentFields) {
                return $contentFields;
            }
        ]));

        TypeLoader::registerType($typeName, function () use ($tableRowType) { return $tableRowType ;});

        return [$tableRowType];
    }
}
