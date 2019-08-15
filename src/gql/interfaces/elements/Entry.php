<?php
namespace craft\gql\interfaces\elements;

use craft\elements\Entry as EntryElement;
use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use craft\gql\types\generators\EntryType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 */
class Entry extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return EntryType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::class, new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
            'description' => 'This is the interface implemented by all entries.',
            'resolveType' => function (EntryElement $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (EntryType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'EntryInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        // Todo section data under section type, same with type, author
        return array_merge(parent::getCommonFields(), [
            'sectionUid' => [
                'name' => 'sectionUid',
                'type' => Type::string(),
                'description' => 'The UID of the section that contains the entry.'
            ],
            'sectionId' => [
                'name' => 'sectionId',
                'type' => Type::int(),
                'description' => 'The ID of the section that contains the entry.'
            ],
            'sectionHandle' => [
                'name' => 'sectionHandle',
                'type' => Type::string(),
                'description' => 'The handle of the section that contains the entry.'
            ],
            'typeUid' => [
                'name' => 'typeUid',
                'type' => Type::string(),
                'description' => 'The UID of the entry type that contains the entry.'
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::int(),
                'description' => 'The ID of the entry type that contains the entry.'
            ],
            'typeHandle' => [
                'name' => 'typeHandle',
                'type' => Type::string(),
                'description' => 'The handle of the entry type that contains the entry.'
            ],
            'authorId' => [
                'name' => 'authorId',
                'type' => Type::int(),
                'description' => 'The ID of the author of this entry.'
            ],
            'author' => [
                'name' => 'author',
                'type' => User::getType(),
                'description' => 'The entry\'s author.'
            ],
            'postDate' => [
                'name' => 'postDate',
                'type' => DateTime::getType(),
                'description' => 'The entry\'s post date.'
            ],
            'expiryDate' => [
                'name' => 'expiryDate',
                'type' => DateTime::getType(),
                'description' => 'The expiry date of the entry.'
            ],
        ]);
    }
}
