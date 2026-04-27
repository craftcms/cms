<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\Types\QueryArgument;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\Volumes;
use GraphQL\Type\Definition\Type;
use Override;

class Asset extends ElementArguments
{
    #[Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'volumeId' => [
                'name' => 'volumeId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.',
            ],
            'volume' => [
                'name' => 'volume',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the volumes the assets belong to, per the volumes’ handles.',
            ],
            'folderId' => [
                'name' => 'folderId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the folders the assets belong to, per the folders’ IDs.',
            ],
            'filename' => [
                'name' => 'filename',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ filenames.',
            ],
            'kind' => [
                'name' => 'kind',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ file kinds.',
            ],
            'height' => [
                'name' => 'height',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ image heights.',
            ],
            'width' => [
                'name' => 'width',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ image widths.',
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the assets’ file sizes (in bytes).',
            ],
            'dateModified' => [
                'name' => 'dateModified',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the assets’ files’ last-modified dates.',
            ],
            'hasAlt' => [
                'name' => 'hasAlt',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on whether the assets have alternative text.',
            ],
            'includeSubfolders' => [
                'name' => 'includeSubfolders',
                'type' => Type::boolean(),
                'description' => 'Broadens the query results to include assets from any of the subfolders of the folder specified by `folderId`.',
            ],
            'withTransforms' => [
                'name' => 'withTransforms',
                'type' => Type::listOf(Type::string()),
                'description' => 'A list of transform handles to preload.',
            ],
            'uploader' => [
                'name' => 'uploader',
                'type' => QueryArgument::getType(),
                'description' => 'Narrows the query results based on the user the assets were uploaded by, per the user’s ID.',
            ],
        ]);
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
    public static function getDraftArguments(): array
    {
        return [];
    }

    #[Override]
    public static function getRevisionArguments(): array
    {
        return [];
    }
}
