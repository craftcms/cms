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
        return array_merge(parent::getArguments(), self::getContentArguments(), static::queryParamArguments(AssetElement::class, [
            'volumeId',
            'volume',
            'folderId',
            'filename',
            'kind',
            'orientation',
            'imageType',
            'height',
            'width',
            'size',
            'dateModified',
            'hasAlt',
            'includeSubfolders',
            'withTransforms',
            'uploader',
        ]));
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

    #[Override]
    /** @return array<string, ArgumentConfig> */
    public static function getDraftArguments(): array
    {
        return [];
    }

    #[Override]
    /** @return array<string, ArgumentConfig> */
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
