<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\arguments\elements\User as UserArguments;
use craft\gql\arguments\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;
use craft\gql\types\generators\AssetType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Asset extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return AssetType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all assets.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        AssetType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'AssetInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        // @TODO Remove the `uri` field for Assets.
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), self::getConditionalFields(), [
            'volumeId' => [
                'name' => 'volumeId',
                'type' => Type::int(),
                'description' => 'The ID of the volume that the asset belongs to.'
            ],
            'folderId' => [
                'name' => 'folderId',
                'type' => Type::int(),
                'description' => 'The ID of the folder that the asset belongs to.'
            ],
            'filename' => [
                'name' => 'filename',
                'type' => Type::string(),
                'description' => 'The filename of the asset file.'
            ],
            'extension' => [
                'name' => 'extension',
                'type' => Type::string(),
                'description' => 'The file extension for the asset file.'
            ],
            'hasFocalPoint' => [
                'name' => 'hasFocalPoint',
                'type' => Type::boolean(),
                'description' => 'Whether a user-defined focal point is set on the asset.'
            ],
            'focalPoint' => [
                'name' => 'focalPoint',
                'type' => Type::listOf(Type::float()),
                'description' => 'The focal point represented as an array with `x` and `y` keys, or null if it\'s not an image.'
            ],
            'kind' => [
                'name' => 'kind',
                'type' => Type::string(),
                'description' => 'The file kind.'
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::string(),
                'description' => 'The file size in bytes.'
            ],
            'height' => [
                'name' => 'height',
                'args' => Transform::getArguments(),
                'type' => Type::int(),
                'description' => 'The height in pixels or null if it\'s not an image.'
            ],
            'width' => [
                'name' => 'width',
                'args' => Transform::getArguments(),
                'type' => Type::int(),
                'description' => 'The width in pixels or null if it\'s not an image.'
            ],
            'img' => [
                'name' => 'img',
                'type' => Type::string(),
                'description' => 'An `<img>` tag based on this asset.'
            ],
            'srcset' => [
                'name' => 'srcset',
                'type' => Type::string(),
                'args' => [
                    'sizes' => [
                        'name' => 'sizes',
                        'description' => 'A list of size descriptors. If you pass x-descriptors, it will be assumed that the image’s current width is the indented 1x width.',
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string())))
                    ]
                ],
                'description' => 'Returns a `srcset` attribute value based on the given widths or x-descriptors.'
            ],
            'url' => [
                'name' => 'url',
                'args' => Transform::getArguments(),
                'type' => Type::string(),
                'description' => 'The full URL of the asset. This field accepts the same fields as the `transform` directive.'
            ],
            'mimeType' => [
                'name' => 'mimeType',
                'type' => Type::string(),
                'description' => 'The file’s MIME type, if it can be determined.'
            ],
            'path' => [
                'name' => 'path',
                'type' => Type::string(),
                'description' => 'The asset\'s path in the volume.'
            ],
            'dateModified' => [
                'name' => 'dateModified',
                'type' => DateTime::getType(),
                'description' => 'The date the asset file was last modified.'
            ],
            'prev' => [
                'name' => 'prev',
                'type' => self::getType(),
                'args' => AssetArguments::getArguments(),
                'description' => 'Returns the previous element relative to this one, from a given set of criteria. CAUTION: Applying arguments to this field severely degrades the performance of the query.',
            ],
            'next' => [
                'name' => 'next',
                'type' => self::getType(),
                'args' => AssetArguments::getArguments(),
                'description' => 'Returns the next element relative to this one, from a given set of criteria. CAUTION: Applying arguments to this field severely degrades the performance of the query.',
            ],
        ]), self::getName());
    }

    /**
     * @inheritdoc
     */
    protected static function getConditionalFields(): array
    {
        if (Gql::canQueryUsers()) {
            return [
                'uploaderId' => [
                    'name' => 'uploaderId',
                    'type' => Type::int(),
                    'description' => 'The ID of the user who first added this asset (if known).'
                ],
                'uploader' => [
                    'name' => 'uploader',
                    'type' => User::getType(),
                    'args' => UserArguments::getArguments(),
                    'description' => 'The user who first added this asset (if known).'
                ],
            ];
        }

        return [];
    }
}
