<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Queries;

use Closure;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Types\EntryType;
use CraftCms\Cms\Gql\Types\Section;
use CraftCms\Cms\Gql\Types\Site;
use CraftCms\Cms\Gql\Types\UserGroup;
use CraftCms\Cms\Gql\Types\Volume;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;

/**
 * Defines read-only queries for sites, sections, entry types, volumes, and user groups.
 *
 * @phpstan-import-type UnnamedFieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class Settings extends Query
{
    /** @return array<string, UnnamedFieldDefinitionConfig> */
    public static function getQueries(bool $checkToken = true): array
    {
        return array_merge(
            self::createQueries('sites', 'sites', 'site', Site::getType(), 'sites', self::sites(...), $checkToken),
            self::createQueries('sections', 'sections', 'section', Section::getType(), 'sections', fn () => Sections::getAllSections()->all(), $checkToken),
            self::createQueries('entrytypes', 'entryTypes', 'entryType', EntryType::getType(), 'entry types', fn () => EntryTypes::getAllEntryTypes()->all(), $checkToken),
            self::createQueries('volumes', 'volumes', 'volume', Volume::getType(), 'volumes', fn () => Volumes::getAllVolumes()->all(), $checkToken),
            self::createQueries('usergroups', 'userGroups', 'userGroup', UserGroup::getType(), 'user groups', fn () => UserGroups::getAllGroups()->all(), $checkToken),
        );
    }

    /**
     * Returns plural and singular queries for a type of settings model.
     *
     * @param  string  $component  The schema component name, without the `settings.` prefix
     * @param  string  $plural  The plural query name
     * @param  string  $singular  The singular query name
     * @param  Type  $type  The GraphQL type
     * @param  string  $description  A description of what’s being queried
     * @param  Closure(): object[]  $getAll  A callable that returns all of the models
     * @return array<string, UnnamedFieldDefinitionConfig>
     */
    public static function createQueries(
        string $component,
        string $plural,
        string $singular,
        Type $type,
        string $description,
        Closure $getAll,
        bool $checkToken = true,
    ): array {
        if ($checkToken && ! GqlHelper::canSchema("settings.$component")) {
            return [];
        }

        return [
            $plural => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull($type))),
                'args' => self::arguments(),
                'resolve' => fn ($source, array $arguments) => self::filter($getAll(), $arguments),
                'description' => "This query is used to query for $description.",
            ],
            $singular => [
                'type' => $type,
                'args' => self::arguments(),
                'resolve' => fn ($source, array $arguments) => self::filter($getAll(), $arguments)[0] ?? null,
                'description' => "This query is used to query for a single $singular.",
            ],
        ];
    }

    /**
     * Returns the sites that can be queried.
     *
     * Disabled sites are only included if the schema can query elements in them.
     *
     * @return SiteData[]
     */
    private static function sites(): array
    {
        return Sites::getAllSites(true)
            ->filter(fn (SiteData $site) => $site->getEnabled() || GqlHelper::canSchema("sites.$site->uid"))
            ->values()
            ->all();
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
            'handle' => [
                'name' => 'handle',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on their handles.',
            ],
        ];
    }

    /**
     * Filters the given models by the query arguments.
     *
     * @param  object[]  $models
     * @param  array<string, mixed>  $arguments
     * @return object[]
     */
    private static function filter(array $models, array $arguments): array
    {
        foreach (['id', 'uid', 'handle'] as $name) {
            if (isset($arguments[$name])) {
                $values = $arguments[$name];
                $models = array_filter($models, fn (object $model) => in_array($model->$name, $values, true));
            }
        }

        return array_values($models);
    }
}
