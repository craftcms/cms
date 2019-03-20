<?php
namespace craft\gql\types\fields;

use craft\gql\interfaces\Field;
use craft\helpers\Json;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRow
 */
class TableRow extends BaseField
{
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'TableRow',
            'fields' => [
                'cells' => [
                    'type' => Type::listOf(TableCell::getType()),
                    'name' => 'cells',
                    'resolve' => function ($value) {
                        $output = [];

                        // Set it up so TableCell can recognize it.
                        if (is_array($value)) {
                            foreach ($value as $key => $content) {
                                $output[] = ['columnKey' => $key, 'content' => $content];
                            }
                        }

                        return $output;
                    }
                ]
            ],
        ]));
    }
}
