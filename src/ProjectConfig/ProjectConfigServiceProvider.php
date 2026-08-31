<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\ProjectConfig\Commands\ApplyCommand;
use CraftCms\Cms\ProjectConfig\Commands\DiffCommand;
use CraftCms\Cms\ProjectConfig\Commands\ExportCommand;
use CraftCms\Cms\ProjectConfig\Commands\GetCommand;
use CraftCms\Cms\ProjectConfig\Commands\RebuildCommand;
use CraftCms\Cms\ProjectConfig\Commands\RemoveCommand;
use CraftCms\Cms\ProjectConfig\Commands\RepairCommand;
use CraftCms\Cms\ProjectConfig\Commands\SetCommand;
use CraftCms\Cms\ProjectConfig\Commands\TouchCommand;
use CraftCms\Cms\ProjectConfig\Commands\WriteCommand;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\ImageTransforms;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Facades\Volumes;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;

class ProjectConfigServiceProvider extends ServiceProvider
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
            RepairCommand::class,
            SetCommand::class,
            RebuildCommand::class,
            RemoveCommand::class,
            TouchCommand::class,
            WriteCommand::class,
        ]);

        $projectConfig
            // System settings
            ->onAdd(ProjectConfig::PATH_SYSTEM, fn (ConfigEvent $event) => Cms::setDefaultTimezone($event->newValue['timeZone'] ?? config('app.timezone', 'UTC')))
            ->onUpdate(ProjectConfig::PATH_SYSTEM, fn (ConfigEvent $event) => Cms::setDefaultTimezone($event->newValue['timeZone'] ?? config('app.timezone', 'UTC')))
            ->onRemove(ProjectConfig::PATH_SYSTEM, fn () => Cms::setDefaultTimezone(config('app.timezone', 'UTC')))
            ->onAdd(ProjectConfig::PATH_SYSTEM.'.timeZone', fn (ConfigEvent $event) => Cms::setDefaultTimezone($event->newValue))
            ->onUpdate(ProjectConfig::PATH_SYSTEM.'.timeZone', fn (ConfigEvent $event) => Cms::setDefaultTimezone($event->newValue))
            ->onRemove(ProjectConfig::PATH_SYSTEM.'.timeZone', fn () => Cms::setDefaultTimezone(config('app.timezone', 'UTC')))
            // Address field layout
            ->onAdd(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, fn (ConfigEvent $event) => app(Addresses::class)->handleChangedAddressFieldLayout($event))
            ->onUpdate(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, fn (ConfigEvent $event) => app(Addresses::class)->handleChangedAddressFieldLayout($event))
            ->onRemove(ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS, fn (ConfigEvent $event) => app(Addresses::class)->handleChangedAddressFieldLayout($event))
            // Asset Transformers
            ->onAdd(ProjectConfig::PATH_ASSET_TRANSFORMERS.'.{uid}', fn (ConfigEvent $event) => app(AssetTransformers::class)->handleChangedAssetTransformer($event))
            ->onUpdate(ProjectConfig::PATH_ASSET_TRANSFORMERS.'.{uid}', fn (ConfigEvent $event) => app(AssetTransformers::class)->handleChangedAssetTransformer($event))
            ->onRemove(ProjectConfig::PATH_ASSET_TRANSFORMERS.'.{uid}', fn (ConfigEvent $event) => app(AssetTransformers::class)->handleDeletedAssetTransformer($event))
            ->onRemove(ProjectConfig::PATH_ASSET_TRANSFORMERS, fn () => app(AssetTransformers::class)->handleAssetTransformersRemoved())
            // Fields
            ->onAdd(ProjectConfig::PATH_FIELDS.'.{uid}', fn (ConfigEvent $event) => Fields::handleChangedField($event))
            ->onUpdate(ProjectConfig::PATH_FIELDS.'.{uid}', fn (ConfigEvent $event) => Fields::handleChangedField($event))
            ->onRemove(ProjectConfig::PATH_FIELDS.'.{uid}', fn (ConfigEvent $event) => Fields::handleDeletedField($event))
            // Filesystems
            ->onAdd(ProjectConfig::PATH_FS.'.{uid}', fn (ConfigEvent $event) => app(Filesystems::class)->handleChangedFilesystem($event))
            ->onUpdate(ProjectConfig::PATH_FS.'.{uid}', fn (ConfigEvent $event) => app(Filesystems::class)->handleChangedFilesystem($event))
            ->onRemove(ProjectConfig::PATH_FS.'.{uid}', fn (ConfigEvent $event) => app(Filesystems::class)->handleDeletedFilesystem($event))
            // Volumes
            ->onAdd(ProjectConfig::PATH_VOLUMES.'.{uid}', fn (ConfigEvent $event) => Volumes::handleChangedVolume($event))
            ->onUpdate(ProjectConfig::PATH_VOLUMES.'.{uid}', fn (ConfigEvent $event) => Volumes::handleChangedVolume($event))
            ->onRemove(ProjectConfig::PATH_VOLUMES.'.{uid}', fn (ConfigEvent $event) => Volumes::handleDeletedVolume($event))
            // Transforms
            ->onAdd(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', fn (ConfigEvent $event) => ImageTransforms::handleChangedTransform($event))
            ->onUpdate(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', fn (ConfigEvent $event) => ImageTransforms::handleChangedTransform($event))
            ->onRemove(ProjectConfig::PATH_IMAGE_TRANSFORMS.'.{uid}', fn (ConfigEvent $event) => ImageTransforms::handleDeletedTransform($event))
            // Site groups
            ->onAdd(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', fn (ConfigEvent $event) => SiteGroups::handleChangedGroup($event))
            ->onUpdate(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', fn (ConfigEvent $event) => SiteGroups::handleChangedGroup($event))
            ->onRemove(ProjectConfig::PATH_SITE_GROUPS.'.{uid}', fn (ConfigEvent $event) => SiteGroups::handleDeletedGroup($event))
            // Sites
            ->onAdd(ProjectConfig::PATH_SITES.'.{uid}', fn (ConfigEvent $event) => Sites::handleChangedSite($event))
            ->onUpdate(ProjectConfig::PATH_SITES.'.{uid}', fn (ConfigEvent $event) => Sites::handleChangedSite($event))
            ->onRemove(ProjectConfig::PATH_SITES.'.{uid}', fn (ConfigEvent $event) => Sites::handleDeletedSite($event))
            // User group permissions
            ->onAdd(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', fn (ConfigEvent $event) => UserPermissions::handleChangedGroupPermissions($event))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', fn (ConfigEvent $event) => UserPermissions::handleChangedGroupPermissions($event))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS.'.{uid}.permissions', fn (ConfigEvent $event) => UserPermissions::handleChangedGroupPermissions($event))
            // User groups
            ->onAdd(ProjectConfig::PATH_USER_GROUPS.'.{uid}', fn (ConfigEvent $event) => UserGroups::handleChangedUserGroup($event))
            ->onUpdate(ProjectConfig::PATH_USER_GROUPS.'.{uid}', fn (ConfigEvent $event) => UserGroups::handleChangedUserGroup($event))
            ->onRemove(ProjectConfig::PATH_USER_GROUPS.'.{uid}', fn (ConfigEvent $event) => UserGroups::handleDeletedUserGroup($event))
            // User field layout
            ->onAdd(ProjectConfig::PATH_USER_FIELD_LAYOUTS, fn (ConfigEvent $event) => Users::handleChangedUserFieldLayout($event))
            ->onUpdate(ProjectConfig::PATH_USER_FIELD_LAYOUTS, fn (ConfigEvent $event) => Users::handleChangedUserFieldLayout($event))
            ->onRemove(ProjectConfig::PATH_USER_FIELD_LAYOUTS, fn (ConfigEvent $event) => Users::handleChangedUserFieldLayout($event))
            // Sections
            ->onAdd(ProjectConfig::PATH_SECTIONS.'.{uid}', fn (ConfigEvent $event) => Sections::handleChangedSection($event))
            ->onUpdate(ProjectConfig::PATH_SECTIONS.'.{uid}', fn (ConfigEvent $event) => Sections::handleChangedSection($event))
            ->onRemove(ProjectConfig::PATH_SECTIONS.'.{uid}', fn (ConfigEvent $event) => Sections::handleDeletedSection($event))
            // Entry types
            ->onAdd(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', fn (ConfigEvent $event) => EntryTypes::handleChangedEntryType($event))
            ->onUpdate(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', fn (ConfigEvent $event) => EntryTypes::handleChangedEntryType($event))
            ->onRemove(ProjectConfig::PATH_ENTRY_TYPES.'.{uid}', fn (ConfigEvent $event) => EntryTypes::handleDeletedEntryType($event))
            // GraphQL schemas
            ->onAdd(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.{uid}', fn (ConfigEvent $event) => Gql::handleChangedSchema($event))
            ->onUpdate(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.{uid}', fn (ConfigEvent $event) => Gql::handleChangedSchema($event))
            ->onRemove(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.{uid}', fn (ConfigEvent $event) => Gql::handleDeletedSchema($event))
            // GraphQL public token
            ->onAdd(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, fn (ConfigEvent $event) => Gql::handleChangedPublicToken($event))
            ->onUpdate(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, fn (ConfigEvent $event) => Gql::handleChangedPublicToken($event));
    }
}
