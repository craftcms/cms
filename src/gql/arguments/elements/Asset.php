<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use craft\gql\base\ElementArguments;
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
        return array_merge(parent::getArguments(), [
            'volumeId' => [
                'name' => 'volumeId',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.'
            ],
            'volume' => [
                'name' => 'volume',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the volumes the assets belong to, per the volumes’ handles.'
            ],
            'folderId' => [
                'name' => 'folderId',
                'type' => Type::listOf(Type::int()),
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
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the assets’ image heights.'
            ],
            'width' => [
                'name' => 'width',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the assets’ image widths.'
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::string(),
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
        ]);
    }
}
