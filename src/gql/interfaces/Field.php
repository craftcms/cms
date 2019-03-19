<?php
namespace craft\gql\interfaces;

use craft\base\Field as BaseField;
use craft\fields\PlainText as PlainTextField;
use craft\gql\common\SchemaObject;
use craft\gql\types\FieldGroup;
use craft\gql\types\fields\PlainText;
use craft\gql\types\fields\UnsupportedField;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Field
 */
class Field extends SchemaObject {
    public static function getType(): Type
    {
        return static::hasType(self::class) ?: static::createType(self::class,new InterfaceType([
            'name' => 'FieldInterface',
            'fields' => function () {
                return array_merge(self::getCommonFields(), [
                    'fieldGroup' => FieldGroup::getType(),
                    'name' => Type::nonNull(Type::string()),
                    'handle' => Type::nonNull(Type::string()),
                    'context' => Type::nonNull(Type::string()),
                    'instructions' => Type::string(),
                    'searchable' => Type::nonNull(Type::boolean()),
                    'translationMethod' => Type::nonNull(Type::string()),
                    'translationKeyFormat' => Type::string(),
                    'fieldType' => Type::nonNull(Type::string()),
                ]);
            },
            'resolveType' => function (BaseField $value) {
                switch (get_class($value)) {
                    case PlainTextField::class:
                        return PlainText::getType();
                    default:
                        return UnsupportedField::getType();
                }
            }
        ]));
    }
}
