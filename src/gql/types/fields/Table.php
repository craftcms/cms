<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use craft\helpers\Json;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Table
 */
class Table extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'TableField',
            'fields' => function () {
                return array_merge(self::getBaseFields(), [
                    'addRowLabel' => Type::string(),
                    'maxRows' => [
                        'name' => 'maxRows',
                        'type' => Type::getNullableType(Type::int()),
                        'resolve' => function ($value) {
                            return is_numeric($value->maxRows) ? (int)$value->maxRows : null;
                        }
                    ],
                    'minRows' => [
                        'name' => 'minRows',
                        'type' => Type::getNullableType(Type::int()),
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
            },
            'interfaces' => [
                Field::getType()
            ]
        ]));
    }
}
