<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class File
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class File extends InputObjectType
{
    public static function getType()
    {
        $typeName = 'FileInput';

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => [
                'fileData' => [
                    'name' => 'fileData',
                    'type' => Type::string(),
                    'description' => 'The contents of the file in Base64 format. If provided, takes precedence over the URL.'
                ],
                'filename' => [
                    'name' => 'filename',
                    'type' => Type::string(),
                    'description' => 'The file name to use (including the extension) data with the `fileData` field.',
                ],
                'url' => [
                    'name' => 'url',
                    'type' => Type::string(),
                    'description' => 'The URL of the file.'
                ],
            ]
        ]));
    }
}
