<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use craft\gql\types\enums\TransformInterlace;
use craft\gql\types\enums\TransformMode;
use craft\gql\types\enums\TransformPosition;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class AssetTransform
 */
class AssetTransform extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'AssetTransform';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return array_merge(parent::getCommonFields(), [
            'name' => Type::nonNull(Type::string()),
            'handle' => Type::nonNull(Type::string()),
            'width' => Type::int(),
            'height' => Type::int(),
            'format' => Type::string(),
            'quality' => Type::int(),
            'dimensionChangeTime' => DateTimeType::getType(),
            'mode' => Type::nonNull(TransformMode::getType()),
            'position' => Type::nonNull(TransformPosition::getType()),
            'interlace' => Type::nonNull(TransformInterlace::getType()),
        ]);
    }
}
