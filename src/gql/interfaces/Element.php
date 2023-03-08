<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces;

use Craft;
use craft\gql\base\InterfaceType;
use craft\gql\base\SingularTypeInterface;
use craft\gql\GqlEntityRegistry;
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
class Element extends InterfaceType implements SingularTypeInterface
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
    public static function getType(): Type
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
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            Gql::GRAPHQL_COUNT_FIELD => [
                'name' => Gql::GRAPHQL_COUNT_FIELD,
                'type' => Type::int(),
                'args' => [
                    'field' => [
                        'name' => 'field',
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The handle of the field that holds the relations.',
                    ],
                ],
                'description' => 'Return a number of related elements for a field.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ],
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'The element’s title.',
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'The element’s slug.',
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::string(),
                'description' => 'The element’s URI.',
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element is enabled or not.',
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Whether the element is archived or not.',
            ],
            'siteHandle' => [
                'name' => 'siteHandle',
                'type' => Type::string(),
                'description' => 'The handle of the site the element is associated with.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'The ID of the site the element is associated with.',
            ],
            'siteSettingsId' => [
                'name' => 'siteSettingsId',
                'type' => Type::id(),
                'description' => 'The unique identifier for an element-site relation.',
            ],
            'language' => [
                'name' => 'language',
                'type' => Type::string(),
                'description' => 'The language of the site element is associated with.',
            ],
            'searchScore' => [
                'name' => 'searchScore',
                'type' => Type::int(),
                'description' => 'The element’s search score, if the `search` parameter was used when querying for the element.',
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Whether the element has been soft-deleted or not.',
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The element’s status.',
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => DateTime::getType(),
                'description' => 'The date the element was created.',
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => DateTime::getType(),
                'description' => 'The date the element was last updated.',
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
            'revisionId' => [
                'name' => 'revisionId',
                'type' => Type::int(),
                'description' => 'The revision ID (from the `revisions` table).',
            ],
            'revisionNotes' => [
                'name' => 'revisionNotes',
                'type' => Type::String(),
                'description' => 'The revision notes (from the `revisions` table).',
            ],
            'draftId' => [
                'name' => 'draftId',
                'type' => Type::int(),
                'description' => 'The draft ID (from the `drafts` table).',
            ],
            'isUnpublishedDraft' => [
                'name' => 'isUnpublishedDraft',
                'type' => Type::boolean(),
                'description' => 'Returns whether this is an unpublished draft.',
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
            ],
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
