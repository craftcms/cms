<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\Arguments\ElementMutationArguments;
use CraftCms\Cms\Gql\Types\Input\File;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
class Asset extends ElementMutationArguments
{
    /** @return array<string, ArgumentConfig> */
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            '_file' => [
                'name' => '_file',
                'description' => 'The file to use for this asset',
                'type' => File::getType(),
            ],
        ], static::propertyArguments(AssetElement::class, ['alt', 'newFolderId', 'uploaderId']), [
            'focalPoint' => [
                'name' => 'focalPoint',
                'description' => 'The image focal point, in the format of "0.5;0.5".',
                'type' => Type::string(),
            ],
        ]);
    }
}
