<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\interfaces\Field;
use GraphQL\Type\Definition\Type;

/**
 * Class MatrixBlockType
 */
class MatrixBlockType extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        return array_merge(parent::getCommonFields(), [
            'name' => Type::nonNull(Type::string()),
            'handle' => Type::string(),
            'sortOrder' => Type::int(),
            'blockTypeFields' => [
                'name' => 'blockTypeFields',
                'type' => Type::listOf(Field::getType()),
                'resolve' => function ($value) {
                    /** @var \craft\models\MatrixBlockType $value */
                    return $value->getFields();
                }
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'MatrixBlockType';
    }
}
