<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Table
 */
class Table extends BaseField
{
    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'TableField',
            'fields' => self::class . '::getFields',
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        return array_merge(parent::getCommonFields(), [
            'addRowLabel' => Type::string(),
            'maxRows' => [
                'name' => 'maxRows',
                'type' => Type::int(),
                'resolve' => function ($value) {
                    return is_numeric($value->maxRows) ? (int)$value->maxRows : null;
                }
            ],
            'minRows' => [
                'name' => 'minRows',
                'type' => Type::int(),
                'resolve' => function ($value) {
                    return is_numeric($value->minRows) ? (int)$value->minRows : null;
                }
            ],
            'columns' => [
                'name' => 'columns',
                'type' => Type::listOf(TableColumn::getType()),
                'resolve' => function ($value) {
                    $output = $value->columns;
                    if (is_array($value->columns)) {
                        // Add the key alongside the definition
                        foreach ($output as $key => &$column) {
                            $column['key'] = $key;
                        }
                    }

                    return $output;
                },
            ],
            'defaults' => Type::listOf(TableRow::getType()),
            'columnType' => Type::nonNull(Type::string()),
        ]);
    }
}
