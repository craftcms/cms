<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use Craft;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\Structure;
use craft\gql\types\DateTime;
use craft\gql\types\generators\EntryType;
use craft\helpers\Gql;
use craft\services\Gql as GqlService;
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
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all entries.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        EntryType::generateTypes();

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
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), static::getDraftFieldDefinitions(), self::getConditionalFields(), [
            'canonicalId' => [
                'name' => 'canonicalId',
                'type' => Type::int(),
                'description' => 'Returns the entry’s canonical ID.',
            ],
            'canonicalUid' => [
                'name' => 'canonicalUid',
                'type' => Type::string(),
                'description' => 'Returns the entry’s canonical UUID.',
            ],
            'sourceId' => [
                'name' => 'sourceId',
                'type' => Type::int(),
                'description' => 'Returns the entry’s canonical ID.',
                'deprecationReason' => 'this field has been deprecated since Craft 3.7.7. Use `canonicalId` instead.',
            ],
            'sourceUid' => [
                'name' => 'sourceUid',
                'type' => Type::string(),
                'description' => 'Returns the entry’s canonical UUID.',
                'deprecationReason' => 'this field has been deprecated since Craft 3.7.7. Use `canonicalUid` instead.',
            ],
            'sectionId' => [
                'name' => 'sectionId',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the section that contains the entry.',
            ],
            'sectionHandle' => [
                'name' => 'sectionHandle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the section that contains the entry.',
                'complexity' => Gql::singleQueryComplexity(),
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the entry type that contains the entry.',
            ],
            'typeHandle' => [
                'name' => 'typeHandle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the entry type that contains the entry.',
                'complexity' => Gql::singleQueryComplexity(),
            ],
            'postDate' => [
                'name' => 'postDate',
                'type' => DateTime::getType(),
                'description' => 'The entry’s post date.',
            ],
            'expiryDate' => [
                'name' => 'expiryDate',
                'type' => DateTime::getType(),
                'description' => 'The expiry date of the entry.',
            ],
            'children' => [
                'name' => 'children',
                'args' => EntryArguments::getArguments(),
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The entry’s children, if the section is a structure. Accepts the same arguments as the `entries` query.',
                'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'descendants' => [
                'name' => 'descendants',
                'args' => EntryArguments::getArguments(),
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The entry’s descendants, if the section is a structure. Accepts the same arguments as the `entries` query.',
                'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'parent' => [
                'name' => 'parent',
                'args' => EntryArguments::getArguments(),
                'type' => EntryInterface::getType(),
                'description' => 'The entry’s parent, if the section is a structure.',
                'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'ancestors' => [
                'name' => 'ancestors',
                'args' => EntryArguments::getArguments(),
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The entry’s ancestors, if the section is a structure. Accepts the same arguments as the `entries` query.',
                'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'url' => [
                'name' => 'url',
                'type' => Type::string(),
                'description' => 'The element’s full URL',
            ],
            'localized' => [
                'name' => 'localized',
                'args' => EntryArguments::getArguments(),
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The same element in other locales.',
                'complexity' => Gql::eagerLoadComplexity(),
            ],
            'prev' => [
                'name' => 'prev',
                'type' => self::getType(),
                'args' => EntryArguments::getArguments(),
                'description' => 'Returns the previous element relative to this one, from a given set of criteria.',
                'complexity' => function($childrenComplexity, $args) {
                    return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1 * (int)!empty($args);
                },
            ],
            'next' => [
                'name' => 'next',
                'type' => self::getType(),
                'args' => EntryArguments::getArguments(),
                'description' => 'Returns the next element relative to this one, from a given set of criteria.',
                'complexity' => function($childrenComplexity, $args) {
                    return $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1 * (int)!empty($args);
                },
            ],
        ]), self::getName());
    }

    /**
     * @inheritdoc
     */
    protected static function getConditionalFields(): array
    {
        $fields = [];
        if (Gql::canQueryUsers()) {
            $fields = array_merge($fields, [
                'authorId' => [
                    'name' => 'authorId',
                    'type' => Type::int(),
                    'description' => 'The ID of the author of this entry.',
                ],
                'author' => [
                    'name' => 'author',
                    'type' => User::getType(),
                    'description' => 'The entry’s author.',
                    'complexity' => Gql::eagerLoadComplexity(),
                ],
            ]);
        }

        if (Gql::canQueryDrafts()) {
            $fields = array_merge($fields, [
                'draftCreator' => [
                    'name' => 'draftCreator',
                    'type' => User::getType(),
                    'description' => 'The creator of a given draft.',
                    'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
                'drafts' => [
                    'name' => 'drafts',
                    'args' => EntryArguments::getArguments(),
                    'type' => Type::listOf(EntryInterface::getType()),
                    'description' => 'The drafts for the entry.',
                    'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
            ]);
        }

        if (Gql::canQueryRevisions()) {
            $fields = array_merge($fields, [
                'revisionCreator' => [
                    'name' => 'revisionCreator',
                    'type' => User::getType(),
                    'description' => 'The creator of a given revision.',
                    'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
                'currentRevision' => [
                    'name' => 'currentRevision',
                    'type' => EntryInterface::getType(),
                    'description' => 'The current revision for the entry.',
                    'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
                'revisions' => [
                    'name' => 'revisions',
                    'args' => EntryArguments::getArguments(),
                    'type' => Type::listOf(EntryInterface::getType()),
                    'description' => 'The revisions for the entry.',
                    'complexity' => Gql::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
            ]);
        }
        return $fields;
    }
}
