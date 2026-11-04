<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Enums;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Mcp\Capabilities\AssetTransformers;
use CraftCms\Cms\Mcp\Capabilities\CpNavigations;
use CraftCms\Cms\Mcp\Capabilities\EntryTypes;
use CraftCms\Cms\Mcp\Capabilities\Fields;
use CraftCms\Cms\Mcp\Capabilities\ImageTransforms;
use CraftCms\Cms\Mcp\Capabilities\Routes;
use CraftCms\Cms\Mcp\Capabilities\Sections;
use CraftCms\Cms\Mcp\Capabilities\SiteGroups;
use CraftCms\Cms\Mcp\Capabilities\Sites;
use CraftCms\Cms\Mcp\Capabilities\UserGroups;
use CraftCms\Cms\Mcp\Capabilities\Volumes;
use CraftCms\Cms\Mcp\Capabilities\Workflows;

/**
 * @since 6.0.0
 */
enum ConfigurationType: string
{
    case Sections = 'sections';
    case EntryTypes = 'entry-types';
    case Fields = 'fields';
    case Volumes = 'volumes';
    case Sites = 'sites';
    case SiteGroups = 'site-groups';
    case UserGroups = 'user-groups';
    case ImageTransforms = 'image-transforms';
    case Routes = 'routes';
    case AssetTransformers = 'asset-transformers';
    case Workflows = 'workflows';
    case CpNavigations = 'cp-navigations';

    /** The Craft edition the type needs, if any. */
    public function requiredEdition(): ?Edition
    {
        return match ($this) {
            self::Workflows => Edition::Pro,
            self::CpNavigations => Edition::Team,
            default => null,
        };
    }

    /** Whether the type is available in the current Craft edition. */
    public function available(): bool
    {
        $edition = $this->requiredEdition();

        return $edition === null || Edition::isAtLeast($edition);
    }

    /** @return class-string */
    public function capability(): string
    {
        return match ($this) {
            self::Sections => Sections::class,
            self::EntryTypes => EntryTypes::class,
            self::Fields => Fields::class,
            self::Volumes => Volumes::class,
            self::Sites => Sites::class,
            self::SiteGroups => SiteGroups::class,
            self::UserGroups => UserGroups::class,
            self::ImageTransforms => ImageTransforms::class,
            self::Routes => Routes::class,
            self::AssetTransformers => AssetTransformers::class,
            self::Workflows => Workflows::class,
            self::CpNavigations => CpNavigations::class,
        };
    }

    public function itemKey(): string
    {
        return match ($this) {
            self::Sections => 'section',
            self::EntryTypes => 'entryType',
            self::Fields => 'field',
            self::Volumes => 'volume',
            self::Sites => 'site',
            self::SiteGroups, self::UserGroups => 'group',
            self::ImageTransforms => 'transform',
            self::Routes => 'route',
            self::AssetTransformers => 'transformer',
            self::Workflows => 'workflow',
            self::CpNavigations => 'navigation',
        };
    }

    public function itemsKey(): string
    {
        return match ($this) {
            self::EntryTypes => 'entryTypes',
            self::SiteGroups, self::UserGroups => 'groups',
            self::ImageTransforms => 'transforms',
            self::AssetTransformers => 'transformers',
            self::CpNavigations => 'navigations',
            default => $this->value,
        };
    }

    /** @return list<string> */
    public function notes(): array
    {
        return match ($this) {
            self::EntryTypes, self::Volumes => ['On update, an omitted fieldLayout retains the current layout; null clears it.'],
            self::Fields => ['Creating a field requires a valid, selectable field class from component_types_list with kind: field; component_types_get describes its settings. Updates cannot change the field class. Supplied settings merge with existing settings.'],
            self::UserGroups => ['Assign permissions separately with user_permissions_group_set, which replaces the complete permission list.'],
            self::Routes => ['Lookup accepts UID or computed URI. uriParts contains literal strings or [name, regex] tuples. Lists routes available to the current site.'],
            self::AssetTransformers => ['Lookup accepts UID or handle. component_types_list and component_types_get with kind: asset-transform-driver describe drivers and their settings; settings the driver doesn’t define are dropped. Changing the driver clears settings that aren’t supplied.'],
            self::Workflows => ['Lookup accepts ID or UID. Workflows require Craft Pro. Assign workflows to sections through section settings. component_types_list and component_types_get with kind: workflow-stage describe stage types and their settings. On update, supplied stages replace the current stages.'],
            self::CpNavigations => ['Lookup is userGroup: a user group’s handle or UID, or `default` for the default navigation, which everyone without a group navigation gets. User group navigations require Craft Pro, and build on the default navigation: they start from it and save only what they change, so later changes to the default carry through to them. A group can hide custom sections and links the default adds, but not remove them. A user in several groups gets the first of their groups’ navigations, by position. Read a navigation’s tree with configuration_get and pass the whole edited tree back on update, with every node it had; it replaces the current customization. Custom sections and links can have a `userCondition` (a user condition’s config), so only users who match it see them, admins included. Hiding is cosmetic: a navigation can leave items out but never grant access to them.'],
            default => [],
        };
    }

    /** @return list<string> */
    public function deletionEffects(): array
    {
        return [match ($this) {
            self::Sections => 'Removes the section from project config and soft-deletes the section and its current canonical entries.',
            self::EntryTypes => 'Removes the entry type from project config, deletes its field layout, and soft-deletes the entry type and its current canonical entries.',
            self::Fields => 'Removes global fields from project config, soft-deletes the field record, and runs field-type deletion hooks.',
            self::Volumes => 'Deletes the volume and its associated assets while retaining their files.',
            self::Sites => 'Cannot delete the primary site. options.transferContentTo moves sections and entries that exist only on the deleted site to the target site. Without a target, those sections are deleted.',
            self::SiteGroups => 'Deletes an empty site group. Refuses to delete a group that still has sites assigned.',
            self::UserGroups => 'Removes the user group and its membership and permission assignments.',
            self::ImageTransforms => 'Removes the named image transform definition.',
            self::Routes => 'Removes the route from project config.',
            self::AssetTransformers => 'Removes the asset transformer from project config. Refuses to delete the Craft transformer, the default transformer, or one assigned to a volume.',
            self::Workflows => 'Removes the workflow from project config. Refuses to delete a workflow that is assigned to a section.',
            self::CpNavigations => 'Removes a user group’s navigation, so the group gets the default navigation unchanged. On `default`, resets the default navigation to the one Craft and plugins build.',
        }, 'No configuration undo tool is provided.'];
    }
}
