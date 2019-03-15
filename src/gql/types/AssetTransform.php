<?php
namespace craft\gql\types;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class AssetTransform
 */
class AssetTransform extends BaseType
{
    public static function getType(): ObjectType
    {
        return static::hasType(self::class) ?: static::createType(self::class, new ObjectType([
            'name' => 'AssetTransform',
            'fields' => array_merge(self::getCommonFields(), [
                'name' => Type::nonNull(Type::string()),
                'handle' => Type::nonNull(Type::string()),
                'width' => Type::int(),
                'height' => Type::int(),
                'format' => Type::string(),
                'quality' => Type::int(),
                'dimensionChangeTime' => DateTimeType::instance(),
                'mode' => Type::nonNull(new EnumType([
                    'name' => 'transformMode',
                    'values' => [
                        'stretch',
                        'fit',
                        'crop',
                    ],
                ])), 'position' => Type::nonNull(new EnumType([
                    'name' => 'transformPosition',
                    'values' => [
                        'topLeft' => 'top-left',
                        'topCenter' => 'top-center',
                        'topRight' => 'top-right',
                        'centerLeft' => 'center-left',
                        'centerCenter' => 'center-center',
                        'centerRIght' => 'center-right',
                        'bottomLeft' => 'bottom-left',
                        'bottomCenter' => 'bottom-center',
                        'bottomRight' => 'bottom-right',
                    ],
                ])), 'interlace' => Type::nonNull(new EnumType([
                    'name' => 'transformInterlace',
                    'values' => [
                        'none',
                        'line',
                        'plane',
                        'partition',
                    ]
                ])),
            ]),
        ]));
    }
}
