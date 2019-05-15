<?php
namespace craft\gql\interfaces\elements;

use craft\base\Field as BaseField;
use craft\fields\Assets as AssetsField;
use craft\fields\Matrix as MatrixField;
use craft\fields\PlainText as PlainTextField;
use craft\fields\Table as TableField;
use craft\gql\TypeRegistry;
use craft\gql\types\DateTimeType;
use craft\gql\types\fields\Assets;
use craft\gql\types\fields\Matrix;
use craft\gql\types\fields\PlainText;
use craft\gql\types\fields\Table;
use craft\gql\types\fields\UnsupportedField;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Element
 */
class Entry extends BaseElement
{
    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        return TypeRegistry::getType(self::class) ?: TypeRegistry::createType(self::class, new InterfaceType([
            'name' => 'EntryInterface',
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
    public static function getFields(): array {
        // Todo section data under section type, same with type, author, revisionCreator
        return array_merge(parent::getCommonFields(), [
            'sectionUid' => Type::string(),
            'sectionId' => Type::int(),
            'sectionHandle' => Type::string(),
            'typeUid' => Type::string(),
            'typeId' => Type::int(),
            'typeHandle' => Type::string(),
            'authorId' => Type::int(),
            'postDate' => DateTimeType::instance(),
            'expiryDate' => DateTimeType::instance(),
            'revisionCreatorId' => Type::int(),
            'revisionNotes' => Type::string(),
        ]);
    }
}
