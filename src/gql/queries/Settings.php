<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\queries;

use Craft;
use craft\base\Model;
use craft\gql\base\Query;
use craft\gql\types\CategoryGroup;
use craft\gql\types\EntryType;
use craft\gql\types\Section;
use craft\gql\types\Site;
use craft\gql\types\TagGroup;
use craft\gql\types\UserGroup;
use craft\gql\types\Volume;
use craft\helpers\ArrayHelper;
use craft\helpers\Gql as GqlHelper;
use craft\models\Site as SiteModel;
use GraphQL\Type\Definition\Type;

/**
 * Class Settings
 *
 * Defines read-only queries for sites, sections, entry types, volumes, user groups, category groups, and tag groups.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class Settings extends Query
{
    /**
     * @inheritdoc
     */
    public static function getQueries(bool $checkToken = true): array
    {
        $queries = [];

        foreach (self::definitions() as $component => [$plural, $singular, $type, $description, $getAll]) {
            if ($checkToken && !GqlHelper::canSchema("settings.$component")) {
                continue;
            }

            $queries[$plural] = [
                'type' => Type::nonNull(Type::listOf(Type::nonNull($type))),
                'args' => self::arguments(),
                'resolve' => fn($source, array $arguments) => self::filter($getAll(), $arguments),
                'description' => "This query is used to query for $description.",
            ];

            $queries[$singular] = [
                'type' => $type,
                'args' => self::arguments(),
                'resolve' => fn($source, array $arguments) => self::filter($getAll(), $arguments)[0] ?? null,
                'description' => "This query is used to query for a single $singular.",
            ];
        }

        return $queries;
    }

    /**
     * Returns the query definitions, indexed by their schema component names.
     *
     * Each definition is an array containing the plural query name, the singular query name, the GraphQL type,
     * a description of what’s being queried, and a callable that returns all of the models.
     *
     * @return array
     */
    private static function definitions(): array
    {
        return [
            'sites' => ['sites', 'site', Site::getType(), 'sites', fn() => self::sites()],
            'sections' => ['sections', 'section', Section::getType(), 'sections', fn() => Craft::$app->getEntries()->getAllSections()],
            'entrytypes' => ['entryTypes', 'entryType', EntryType::getType(), 'entry types', fn() => Craft::$app->getEntries()->getAllEntryTypes()],
            'volumes' => ['volumes', 'volume', Volume::getType(), 'volumes', fn() => Craft::$app->getVolumes()->getAllVolumes()],
            'usergroups' => ['userGroups', 'userGroup', UserGroup::getType(), 'user groups', fn() => Craft::$app->getUserGroups()->getAllGroups()],
            'categorygroups' => ['categoryGroups', 'categoryGroup', CategoryGroup::getType(), 'category groups', fn() => Craft::$app->getCategories()->getAllGroups()],
            'taggroups' => ['tagGroups', 'tagGroup', TagGroup::getType(), 'tag groups', fn() => Craft::$app->getTags()->getAllTagGroups()],
        ];
    }

    /**
     * Returns the sites that can be queried.
     *
     * Disabled sites are only included if the schema can query elements in them.
     *
     * @return SiteModel[]
     */
    private static function sites(): array
    {
        return array_values(array_filter(
            Craft::$app->getSites()->getAllSites(true),
            fn(SiteModel $site) => $site->getEnabled() || GqlHelper::canSchema("sites.$site->uid"),
        ));
    }

    /**
     * Returns the arguments that the queries accept.
     *
     * @return array
     */
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
     * @param Model[] $models
     * @param array $arguments
     * @return Model[]
     */
    private static function filter(array $models, array $arguments): array
    {
        foreach (['id', 'uid', 'handle'] as $name) {
            if (isset($arguments[$name])) {
                $values = $arguments[$name];
                $models = array_filter($models, fn(Model $model) => in_array(ArrayHelper::getValue($model, $name), $values, true));
            }
        }

        return array_values($models);
    }
}
