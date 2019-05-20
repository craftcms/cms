<?php
namespace craft\gql\interfaces;

use craft\base\Field as BaseField;
use craft\fields\Assets as AssetsField;
use craft\fields\Matrix as MatrixField;
use craft\fields\PlainText as PlainTextField;
use craft\fields\Table as TableField;
use craft\gql\common\SchemaObject;
use craft\gql\TypeRegistry;
use craft\gql\types\FieldGroup;
use craft\gql\types\fields\Assets;
use craft\gql\types\fields\Matrix;
use craft\gql\types\fields\PlainText;
use craft\gql\types\fields\Table;
use craft\gql\types\fields\UnsupportedField;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Field
 */
class Field extends SchemaObject
{
    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class,new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
            'resolveType' => function (BaseField $value) {
                switch (get_class($value)) {
                    case PlainTextField::class:
                        return PlainText::getType();
                    case AssetsField::class:
                        return Assets::getType();
                    case TableField::class:
                        return Table::getType();
                    case MatrixField::class:
                        return Matrix::getType();
                    default:
                        return UnsupportedField::getType();
                }
            }
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'FieldInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        return array_merge(parent::getCommonFields(), [
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
    }
}
