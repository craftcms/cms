<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces;

use craft\gql\base\InterfaceType;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;
use craft\gql\types\generators\ElementType;
use craft\helpers\Gql as GqlHelper;
use craft\services\Gql;
use GraphQL\Type\Definition\InterfaceType as GqlInterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Element extends InterfaceType
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return ElementType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new GqlInterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all elements.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        ElementType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            Gql::GRAPHQL_COUNT_FIELD => [
                'name' => Gql::GRAPHQL_COUNT_FIELD,
                'type' => Type::int(),
                'args' => [
                    'field' => [
                        'name' => 'field',
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The handle of the field that holds the relations.'
                    ]
                ],
                'description' => 'Return a number of related elements for a field.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ],
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'The element’s title.'
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'The element’s slug.'
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::string(),
                'description' => 'The element’s URI.'
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element is enabled or not.'
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Whether the element is archived or not.'
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'The ID of the site the element is associated with.'
            ],
            'language' => [
                'name' => 'language',
                'type' => Type::string(),
                'description' => 'The language of the site element is associated with.'
            ],
            'searchScore' => [
                'name' => 'searchScore',
                'type' => Type::string(),
                'description' => 'The element’s search score, if the `search` parameter was used when querying for the element.'
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Whether the element has been soft-deleted or not.'
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The element\'s status.'
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => DateTime::getType(),
                'description' => 'The date the element was created.'
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => DateTime::getType(),
                'description' => 'The date the element was last updated.'
            ],
        ]), self::getName());
    }

    /**
     * List the draft field definitions.
     *
     * @return array
     */
    public static function getDraftFieldDefinitions(): array
    {
        return [
            'isDraft' => [
                'name' => 'isDraft',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is a draft.',
            ],
            'isRevision' => [
                'name' => 'isRevision',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is a revision.',
            ],
            'sourceId' => [
                'name' => 'sourceId',
                'type' => Type::int(),
                'description' => 'Returns the element’s ID, or if it’s a draft/revision, its source element’s ID.',
            ],
            'sourceUid' => [
                'name' => 'sourceUid',
                'type' => Type::string(),
                'description' => 'Returns the element’s UUID, or if it’s a draft/revision, its source element’s UUID.',
            ],
            'draftId' => [
                'name' => 'draftId',
                'type' => Type::int(),
                'description' => 'The ID of the draft to return (from the `drafts` table)',
            ],
            'isUnpublishedDraft' => [
                'name' => 'isUnpublishedDraft',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is an unpublished draft.',
            ],
            'isUnsavedDraft' => [
                'name' => 'isUnsavedDraft',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is an unpublished draft. **This field is deprecated.** `isUnpublishedDraft` should be used instead.',
            ],
            'draftName' => [
                'name' => 'draftName',
                'type' => Type::string(),
                'description' => 'The name of the draft.',
            ],
            'draftNotes' => [
                'name' => 'draftNotes',
                'type' => Type::string(),
                'description' => 'The notes for the draft.',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ElementInterface';
    }
}
