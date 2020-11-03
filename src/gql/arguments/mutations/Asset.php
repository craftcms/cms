<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\mutations;

use craft\gql\base\ElementMutationArguments;
use craft\gql\types\input\File;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Asset extends ElementMutationArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            '_file' => [
                'name' => '_file',
                'description' => 'The file to use for this asset',
                'type' => File::getType()
            ],
            'newFolderId' => [
                'name' => 'newFolderId',
                'description' => 'ID of the new folder for this asset',
                'type' => Type::id()
            ],
            'uploaderId' => [
                'name' => 'uploaderId',
                'description' => 'The ID of the user who first added this asset (if known).',
                'type' => Type::id()
            ]
        ]);
    }
}
