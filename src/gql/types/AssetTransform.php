<?php
namespace craft\gql\types;

use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class AssetTransform
 */
class AssetTransform extends SchemaObject
{
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new ObjectType([
            'name' => 'AssetTransform',
            'fields' => self::class . '::getFields',
        ]));
    }

    public static function getFields(): array
    {
        return array_merge(parent::getCommonFields(), [
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
            ])),
            'position' => Type::nonNull(new EnumType([
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
            ])),
            'interlace' => Type::nonNull(new EnumType([
                'name' => 'transformInterlace',
                'values' => [
                    'none',
                    'line',
                    'plane',
                    'partition',
                ]
            ])),
        ]);
    }
}
