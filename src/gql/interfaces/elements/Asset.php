<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use craft\elements\Asset as AssetElement;
use craft\gql\arguments\Transform;
use craft\gql\interfaces\Element;
use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;
use craft\gql\types\generators\AssetType;
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
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::class, new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all assets.',
            'resolveType' => function (AssetElement $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (AssetType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

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
        return array_merge(parent::getFieldDefinitions(), [
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
                'type' => Type::int(),
                'description' => 'The height in pixels or null if it\'s not an image.'
            ],
            'width' => [
                'name' => 'width',
                'type' => Type::int(),
                'description' => 'The width in pixels or null if it\'s not an image.'
            ],
            'img' => [
                'name' => 'img',
                'type' => Type::string(),
                'description' => 'An `<img>` tag based on this asset.'
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

        ]);
    }
}
