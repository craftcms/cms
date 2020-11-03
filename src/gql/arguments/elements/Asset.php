<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Asset extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'volumeId' => [
                'name' => 'volumeId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.'
            ],
            'volume' => [
                'name' => 'volume',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the volumes the assets belong to, per the volumes’ handles.'
            ],
            'folderId' => [
                'name' => 'folderId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the folders the assets belong to, per the folders’ IDs.'
            ],
            'filename' => [
                'name' => 'filename',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ filenames.'
            ],
            'kind' => [
                'name' => 'kind',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ file kinds.'
            ],
            'height' => [
                'name' => 'height',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ image heights.'
            ],
            'width' => [
                'name' => 'width',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ image widths.'
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ file sizes (in bytes).'
            ],
            'dateModified' => [
                'name' => 'dateModified',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the assets’ files’ last-modified dates.'
            ],
            'includeSubfolders' => [
                'name' => 'includeSubfolders',
                'type' => Type::boolean(),
                'description' => 'Broadens the query results to include assets from any of the subfolders of the folder specified by `folderId`.'
            ],
            'withTransforms' => [
                'name' => 'withTransforms',
                'type' => Type::listOf(Type::string()),
                'description' => 'A list of transform handles to preload.'
            ],
            'uploader' => [
                'name' => 'uploader',
                'type' => QueryArgument::getType(),
                'description' => 'Narrows the query results based on the user the assets were uploaded by, per the user’s ID.'
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $volumeFieldArguments = Craft::$app->getGql()->getContentArguments(Craft::$app->getVolumes()->getAllVolumes(), AssetElement::class);
        return array_merge(parent::getContentArguments(), $volumeFieldArguments);
    }
}
