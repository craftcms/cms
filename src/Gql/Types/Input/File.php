<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class File extends InputObjectType
{
    public static function getType(): mixed
    {
        $typeName = 'FileInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => [
                'fileData' => [
                    'name' => 'fileData',
                    'type' => Type::string(),
                    'description' => 'The contents of the file in Base64 format. If provided, takes precedence over the URL.',
                ],
                'filename' => [
                    'name' => 'filename',
                    'type' => Type::string(),
                    'description' => 'The file name to use (including the extension) data with the `fileData` field.',
                ],
                'url' => [
                    'name' => 'url',
                    'type' => Type::string(),
                    'description' => 'The URL of the file.',
                ],
            ],
        ]));
    }
}
