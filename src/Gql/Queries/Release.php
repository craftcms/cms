<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Types\Release as ReleaseType;
use CraftCms\Cms\Release\Models\Release as ReleaseModel;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Builder;

/**
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class Release extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && ! GqlHelper::canQueryReleases()) {
            return [];
        }

        return [
            'releases' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(ReleaseType::getType()))),
                'args' => self::arguments(),
                'resolve' => fn ($source, array $arguments) => self::query($arguments)->get()->all(),
                'description' => 'This query is used to query for releases.',
            ],
            'release' => [
                'type' => ReleaseType::getType(),
                'args' => self::arguments(),
                'resolve' => fn ($source, array $arguments) => self::query($arguments)->first(),
                'description' => 'This query is used to query for a single release.',
            ],
        ];
    }

    /** @return array<string, array{name: string, type: Type, description: string}> */
    private static function arguments(): array
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::listOf(Type::int()),
                'description' => 'Narrows the query results based on their IDs.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on their UUIDs.',
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on their statuses (`draft`, `scheduled`, `publishing`, `published`, or `failed`).',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return Builder<ReleaseModel>
     */
    private static function query(array $arguments): Builder
    {
        return ReleaseModel::query()
            ->when(isset($arguments['id']), fn (Builder $query) => $query->whereIn('id', $arguments['id']))
            ->when(isset($arguments['uid']), fn (Builder $query) => $query->whereIn('uid', $arguments['uid']))
            ->when(isset($arguments['status']), fn (Builder $query) => $query->whereIn('status', $arguments['status']))
            ->orderBy('id');
    }
}
