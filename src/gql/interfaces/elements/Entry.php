<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use craft\elements\Entry as EntryElement;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\Structure;
use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use craft\gql\types\generators\EntryType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends Structure
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
            'fields' => self::class . '::getFieldDefinitions',
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
    public static function getFieldDefinitions(): array {
        return array_merge(parent::getFieldDefinitions(), self::getConditionalFields(), [
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
            'children' => [
                'name' => 'children',
                'args' => EntryArguments::getArguments(),
                'type' => Type::listOf(EntryInterface::getType()),
                'description' => 'The entry’s children, if the section is a structure. Accepts the same arguments as the `entries` query.'
            ],
            'parent' => [
                'name' => 'parent',
                'type' => EntryInterface::getType(),
                'description' => 'The entry’s parent, if the section is a structure.'
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    protected static function getConditionalFields(): array
    {
        if (Gql::canQueryUsers()) {
            return [
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
            ];
        }

        return [];
    }
}
