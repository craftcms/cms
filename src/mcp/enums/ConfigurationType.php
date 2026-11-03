<?php

declare(strict_types=1);

namespace craft\mcp\enums;

use craft\mcp\capabilities\EntryTypes;
use craft\mcp\capabilities\Fields;
use craft\mcp\capabilities\ImageTransforms;
use craft\mcp\capabilities\Routes;
use craft\mcp\capabilities\Sections;
use craft\mcp\capabilities\SiteGroups;
use craft\mcp\capabilities\Sites;
use craft\mcp\capabilities\UserGroups;
use craft\mcp\capabilities\Volumes;

/**
 * @since 5.12.0
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
        };
    }

    public function itemsKey(): string
    {
        return match ($this) {
            self::EntryTypes => 'entryTypes',
            self::SiteGroups, self::UserGroups => 'groups',
            self::ImageTransforms => 'transforms',
            default => $this->value,
        };
    }

    /** @return list<string> */
    public function notes(): array
    {
        return match ($this) {
            self::EntryTypes, self::Volumes => ['On update, an omitted fieldLayout retains the current layout; null clears it.'],
            self::Fields => ['Creating a field requires a valid, selectable field class. Updates cannot change the field class. Supplied settings merge with existing settings.'],
            self::UserGroups => ['Assign permissions separately with user-permissions.group.set, which replaces the complete permission list.'],
            self::Routes => ['Lookup accepts UID or computed URI. uriParts contains literal strings or [name, regex] tuples. Lists routes available to the current site.'],
            default => [],
        };
    }

    /** @return list<string> */
    public function deletionEffects(): array
    {
        return [ match ($this) {
            self::Sections => 'Removes the section from project config and soft-deletes the section and its current canonical entries.',
            self::EntryTypes => 'Removes the entry type from project config, deletes its field layout, and soft-deletes the entry type and its current canonical entries.',
            self::Fields => 'Removes global fields from project config, soft-deletes the field record, and runs field-type deletion hooks.',
            self::Volumes => 'Deletes the volume and its associated assets while retaining their files.',
            self::Sites => 'Cannot delete the primary site. options.transferContentTo moves sections and entries that exist only on the deleted site to the target site. Without a target, those sections are deleted.',
            self::SiteGroups => 'Deletes an empty site group. Refuses to delete a group that still has sites assigned.',
            self::UserGroups => 'Removes the user group and its membership and permission assignments.',
            self::ImageTransforms => 'Removes the named image transform definition.',
            self::Routes => 'Removes the route from project config.',
        }, 'No configuration undo tool is provided.'];
    }
}
