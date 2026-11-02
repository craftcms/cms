<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\Volumes;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
class Asset extends ElementArguments
{
    #[Override]
    public static function getArguments(): array
    {
        // Custom field arguments can’t shadow the asset’s own arguments.
        return array_merge(self::getContentArguments(), parent::getArguments());
    }

    #[Override]
    protected static function elementType(): string
    {
        return AssetElement::class;
    }

    #[Override]
    public static function getContentArguments(): array
    {
        $volumeFieldArguments = Gql::getContentArguments(
            contexts: Volumes::getAllVolumes()->all(),
            elementType: AssetElement::class,
        );

        return array_merge(parent::getContentArguments(), $volumeFieldArguments);
    }
}
