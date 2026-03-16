<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use CraftCms\Cms\Gql\Arguments\ElementMutationArguments;
use CraftCms\Cms\Gql\Types\Input\File;
use GraphQL\Type\Definition\Type;

class Asset extends ElementMutationArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            '_file' => [
                'name' => '_file',
                'description' => 'The file to use for this asset',
                'type' => File::getType(),
            ],
            'alt' => [
                'name' => 'alt',
                'description' => 'Alternative text for the asset.',
                'type' => Type::string(),
            ],
            'newFolderId' => [
                'name' => 'newFolderId',
                'description' => 'ID of the new folder for this asset',
                'type' => Type::id(),
            ],
            'uploaderId' => [
                'name' => 'uploaderId',
                'description' => 'The ID of the user who first added this asset (if known).',
                'type' => Type::id(),
            ],
            'focalPoint' => [
                'name' => 'focalPoint',
                'description' => 'The image focal point, in the format of "0.5;0.5".',
                'type' => Type::string(),
            ],
        ]);
    }
}
