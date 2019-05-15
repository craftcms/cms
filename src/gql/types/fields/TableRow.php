<?php
namespace craft\gql\types\fields;

use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class TableRow
 */
class TableRow extends BaseField
{
    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'TableRow',
            'fields' => self::class . '::getFields',
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return [
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
        ];
    }
}
