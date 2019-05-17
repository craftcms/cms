<?php
namespace craft\gql\interfaces\elements;

use craft\elements\Entry as EntryElement;
use craft\gql\TypeRegistry;
use craft\gql\types\DateTimeType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
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
            'resolveType' => function (EntryElement $value) {
                $entryType = $value->getType();
                return TypeRegistry::getType($entryType->uid);
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
