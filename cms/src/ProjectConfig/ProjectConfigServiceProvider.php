<?php

namespace CraftCms\Cms\ProjectConfig;

use Closure;
use Craft;
use CraftCms\Cms\ProjectConfig\Commands\ApplyCommand;
use CraftCms\Cms\ProjectConfig\Commands\DiffCommand;
use CraftCms\Cms\ProjectConfig\Commands\ExportCommand;
use CraftCms\Cms\ProjectConfig\Commands\GetCommand;
use CraftCms\Cms\ProjectConfig\Commands\RebuildCommand;
use CraftCms\Cms\ProjectConfig\Commands\RemoveCommand;
use CraftCms\Cms\ProjectConfig\Commands\SetCommand;
use CraftCms\Cms\ProjectConfig\Commands\TouchCommand;
use CraftCms\Cms\ProjectConfig\Commands\WriteCommand;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

final class ProjectConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->terminating(function (ProjectConfig $projectConfig) {
            $projectConfig->flush();
        });

        Dispatcher::macro('prependListener', function ($events, $listener) {
            if ($events instanceof Closure) {
                /** @phpstan-ignore method.protected */
                return new Collection($this->firstClosureParameterTypes($events))
                    ->each(function ($event) use ($events) {
                        $this->prependListener($event, $events);
                    });
            }

            if ($events instanceof QueuedClosure) {
                /** @phpstan-ignore method.protected */
                return new Collection($this->firstClosureParameterTypes($events->closure))
                    ->each(function ($event) use ($events) {
                        $this->prependListener($event, $events->resolve());
                    });
            }

            if ($listener instanceof QueuedClosure) {
                $listener = $listener->resolve();
            }

            foreach ((array) $events as $event) {
                if (str_contains($event, '*')) {
                    $this->setupWildcardPrependListen($event, $listener);
                } else {
                    /** @phpstan-ignore property.protected */
                    $this->listeners[$event] ??= [];
                    /** @phpstan-ignore property.protected */
                    array_unshift($this->listeners[$event], $listener);
                }
            }
        });

        Dispatcher::macro('setupWildcardPrependListen', function ($event, $listener) {
            /** @phpstan-ignore property.protected */
            $this->wildcards[$event] ??= [];
            /** @phpstan-ignore property.protected */
            array_unshift($this->wildcards[$event], $listener);
            /** @phpstan-ignore property.protected */
            $this->wildcardsCache = [];
        });
    }

    public function boot(ProjectConfig $projectConfig): void
    {
        $this->commands([
            ApplyCommand::class,
            DiffCommand::class,
            ExportCommand::class,
            GetCommand::class,
            SetCommand::class,
            RebuildCommand::class,
            RemoveCommand::class,
            TouchCommand::class,
            WriteCommand::class,
        ]);

        $projectConfig
            // Address field layout
            ->onAdd(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, $this->proxy('addresses', 'handleChangedAddressFieldLayout'))
            ->onUpdate(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, $this->proxy('addresses', 'handleChangedAddressFieldLayout'))
            ->onRemove(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, $this->proxy('addresses', 'handleChangedAddressFieldLayout'))
            // Fields
            ->onAdd(ProjectConfig::PATH_FIELDS.'.{uid}', $this->proxy('fields', 'handleChangedField'))
            ->onUpdate(ProjectConfig::PATH_FIELDS.'.{uid}', $this->proxy('fields', 'handleChangedField'))
            ->onRemove(ProjectConfig::PATH_FIELDS.'.{uid}', $this->proxy('fields', 'handleDeletedField'))
            // Volumes
            ->onAdd(ProjectConfig::PATH_VOLUMES.'.{uid}', $this->proxy('volumes', 'handleChangedVolume'))
            ->onUpdate(ProjectConfig::PATH_VOLUMES.'.{uid}', $this->proxy('volumes', 'handleChangedVolume'))
            ->onRemove(ProjectConfig::PATH_VOLUMES.'.{uid}', $this->proxy('volumes', 'handleDeletedVolume'))
            // Transforms
            ->onAdd(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', $this->proxy('imageTransforms', 'handleChangedTransform'))
            ->onUpdate(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', $this->proxy('imageTransforms', 'handleChangedTransform'))
            ->onRemove(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', $this->proxy('imageTransforms', 'handleDeletedTransform'))
            // Site groups
            ->onAdd(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', $this->proxy('sites', 'handleChangedGroup'))
            ->onUpdate(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', $this->proxy('sites', 'handleChangedGroup'))
            ->onRemove(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', $this->proxy('sites', 'handleDeletedGroup'))
            // Sites
            ->onAdd(ProjectConfig::PATH_SITES.'.{uid}', $this->proxy('sites', 'handleChangedSite'))
            ->onUpdate(ProjectConfig::PATH_SITES.'.{uid}', $this->proxy('sites', 'handleChangedSite'))
            ->onRemove(ProjectConfig::PATH_SITES.'.{uid}', $this->proxy('sites', 'handleDeletedSite'))
            // Tags
            ->onAdd(ProjectConfig::PATH_TAG_GROUPS.'.{uid}', $this->proxy('tags', 'handleChangedTagGroup'))
            ->onUpdate(ProjectConfig::PATH_TAG_GROUPS.'.{uid}', $this->proxy('tags', 'handleChangedTagGroup'))
            ->onRemove(ProjectConfig::PATH_TAG_GROUPS.'.{uid}', $this->proxy('tags', 'handleDeletedTagGroup'))
            // Categories
            ->onAdd(ProjectConfig::PATH_CATEGORY_GROUPS.'.{uid}', $this->proxy('categories', 'handleChangedCategoryGroup'))
            ->onUpdate(ProjectConfig::PATH_CATEGORY_GROUPS.'.{uid}', $this->proxy('categories', 'handleChangedCategoryGroup'))
            ->onRemove(ProjectConfig::PATH_CATEGORY_GROUPS.'.{uid}', $this->proxy('categories', 'handleDeletedCategoryGroup'))
            // User group permissions
            ->onAdd(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', $this->proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', $this->proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', $this->proxy('userPermissions', 'handleChangedGroupPermissions'))
            // User groups
            ->onAdd(ProjectConfig::PATH_USER_GROUPS.'.{uid}', $this->proxy('userGroups', 'handleChangedUserGroup'))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS.'.{uid}', $this->proxy('userGroups', 'handleChangedUserGroup'))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS.'.{uid}', $this->proxy('userGroups', 'handleDeletedUserGroup'))
            // User field layout
            ->onAdd(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->proxy('users', 'handleChangedUserFieldLayout'))
            ->onUpdate(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->proxy('users', 'handleChangedUserFieldLayout'))
            ->onRemove(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->proxy('users', 'handleChangedUserFieldLayout'))
            // Global sets
            ->onAdd(ProjectConfig::PATH_GLOBAL_SETS.'.{uid}', $this->proxy('globals', 'handleChangedGlobalSet'))
            ->onUpdate(ProjectConfig::PATH_GLOBAL_SETS.'.{uid}', $this->proxy('globals', 'handleChangedGlobalSet'))
            ->onRemove(ProjectConfig::PATH_GLOBAL_SETS.'.{uid}', $this->proxy('globals', 'handleDeletedGlobalSet'))
            // Sections
            ->onAdd(ProjectConfig::PATH_SECTIONS.'.{uid}', $this->proxy('entries', 'handleChangedSection'))
            ->onUpdate(ProjectConfig::PATH_SECTIONS.'.{uid}', $this->proxy('entries', 'handleChangedSection'))
            ->onRemove(ProjectConfig::PATH_SECTIONS.'.{uid}', $this->proxy('entries', 'handleDeletedSection'))
            // Entry types
            ->onAdd(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', $this->proxy('entries', 'handleChangedEntryType'))
            ->onUpdate(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', $this->proxy('entries', 'handleChangedEntryType'))
            ->onRemove(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', $this->proxy('entries', 'handleDeletedEntryType'))
            // GraphQL schemas
            ->onAdd(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.{uid}', $this->proxy('gql', 'handleChangedSchema'))
            ->onUpdate(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.{uid}', $this->proxy('gql', 'handleChangedSchema'))
            ->onRemove(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.{uid}', $this->proxy('gql', 'handleDeletedSchema'))
            // GraphQL public token
            ->onAdd(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, $this->proxy('gql', 'handleChangedPublicToken'))
            ->onUpdate(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, $this->proxy('gql', 'handleChangedPublicToken'));
    }

    /**
     * Returns a proxy function for calling a component method, based on its ID.
     *
     * The component won’t be fetched until the method is called, avoiding unnecessary component instantiation, and ensuring the correct component
     * is called if it happens to get swapped out (e.g. for a test).
     *
     * @param  string  $id  The component ID
     * @param  string  $method  The method name
     */
    private function proxy(string $id, string $method): callable
    {
        return fn () => Craft::$app->get($id)->$method(...func_get_args());
    }
}
