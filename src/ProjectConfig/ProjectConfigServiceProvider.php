<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\Commands\ApplyCommand;
use CraftCms\Cms\ProjectConfig\Commands\DiffCommand;
use CraftCms\Cms\ProjectConfig\Commands\ExportCommand;
use CraftCms\Cms\ProjectConfig\Commands\GetCommand;
use CraftCms\Cms\ProjectConfig\Commands\RebuildCommand;
use CraftCms\Cms\ProjectConfig\Commands\RemoveCommand;
use CraftCms\Cms\ProjectConfig\Commands\SetCommand;
use CraftCms\Cms\ProjectConfig\Commands\TouchCommand;
use CraftCms\Cms\ProjectConfig\Commands\WriteCommand;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

final class ProjectConfigServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        Event::listen(RequestHandled::class, function () {
            $this->flushProjectConfig();
        });

        Event::listen(CommandFinished::class, function () {
            $this->flushProjectConfig();
        });
    }

    private function flushProjectConfig(): void
    {
        if (! Cms::isInstalled()) {
            return;
        }

        app(ProjectConfig::class)->flush();

        if (app(ProjectConfig::class)->waitingToUpdateParsedConfigTimes) {
            app(ProjectConfig::class)->updateParsedConfigTimes();
        }
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
            ->onAdd(ProjectConfig::PATH_FIELDS.'.{uid}', fn (ConfigEvent $event) => Fields::handleChangedField($event))
            ->onUpdate(ProjectConfig::PATH_FIELDS.'.{uid}', fn (ConfigEvent $event) => Fields::handleChangedField($event))
            ->onRemove(ProjectConfig::PATH_FIELDS.'.{uid}', fn (ConfigEvent $event) => Fields::handleDeletedField($event))
            // Volumes
            ->onAdd(ProjectConfig::PATH_VOLUMES.'.{uid}', $this->proxy('volumes', 'handleChangedVolume'))
            ->onUpdate(ProjectConfig::PATH_VOLUMES.'.{uid}', $this->proxy('volumes', 'handleChangedVolume'))
            ->onRemove(ProjectConfig::PATH_VOLUMES.'.{uid}', $this->proxy('volumes', 'handleDeletedVolume'))
            // Transforms
            ->onAdd(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', $this->proxy('imageTransforms', 'handleChangedTransform'))
            ->onUpdate(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', $this->proxy('imageTransforms', 'handleChangedTransform'))
            ->onRemove(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', $this->proxy('imageTransforms', 'handleDeletedTransform'))
            // Site groups
            ->onAdd(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', fn (ConfigEvent $event) => SiteGroups::handleChangedGroup($event))
            ->onUpdate(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', fn (ConfigEvent $event) => SiteGroups::handleChangedGroup($event))
            ->onRemove(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', fn (ConfigEvent $event) => SiteGroups::handleDeletedGroup($event))
            // Sites
            ->onAdd(ProjectConfig::PATH_SITES.'.{uid}', fn (ConfigEvent $event) => Sites::handleChangedSite($event))
            ->onUpdate(ProjectConfig::PATH_SITES.'.{uid}', fn (ConfigEvent $event) => Sites::handleChangedSite($event))
            ->onRemove(ProjectConfig::PATH_SITES.'.{uid}', fn (ConfigEvent $event) => Sites::handleDeletedSite($event))
            // User group permissions
            ->onAdd(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', $this->proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', $this->proxy('userPermissions', 'handleChangedGroupPermissions'))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', $this->proxy('userPermissions', 'handleChangedGroupPermissions'))
            // User groups
            ->onAdd(ProjectConfig::PATH_USER_GROUPS.'.{uid}', fn (ConfigEvent $event) => UserGroups::handleChangedUserGroup($event))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS.'.{uid}', fn (ConfigEvent $event) => UserGroups::handleChangedUserGroup($event))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS.'.{uid}', fn (ConfigEvent $event) => UserGroups::handleDeletedUserGroup($event))
            // User field layout
            ->onAdd(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->proxy('users', 'handleChangedUserFieldLayout'))
            ->onUpdate(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->proxy('users', 'handleChangedUserFieldLayout'))
            ->onRemove(ProjectConfig::PATH_USER_FIELD_LAYOUTS, $this->proxy('users', 'handleChangedUserFieldLayout'))
            // Sections
            ->onAdd(ProjectConfig::PATH_SECTIONS.'.{uid}', fn (ConfigEvent $event) => Sections::handleChangedSection($event))
            ->onUpdate(ProjectConfig::PATH_SECTIONS.'.{uid}', fn (ConfigEvent $event) => Sections::handleChangedSection($event))
            ->onRemove(ProjectConfig::PATH_SECTIONS.'.{uid}', fn (ConfigEvent $event) => Sections::handleDeletedSection($event))
            // Entry types
            ->onAdd(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', fn (ConfigEvent $event) => EntryTypes::handleChangedEntryType($event))
            ->onUpdate(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', fn (ConfigEvent $event) => EntryTypes::handleChangedEntryType($event))
            ->onRemove(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', fn (ConfigEvent $event) => EntryTypes::handleDeletedEntryType($event))
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
