<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Facades\Fields;

/**
 * Flattens a resolved settings Form to the setting names in render order.
 * Group children are prefixed with the group's UID so nesting stays visible.
 *
 * @param  list<NodePayload>  $nodes
 * @return list<string>
 */
function settingNames(array $nodes, string $prefix = ''): array
{
    $names = [];

    foreach ($nodes as $node) {
        if ($node->control !== null) {
            $names[] = $prefix.end($node->control->path);

            continue;
        }

        $uid = $node->uid ?? 'node';

        if ($node->children === null || $node->children === []) {
            $names[] = $prefix.$uid;

            continue;
        }

        $names = [...$names, ...settingNames($node->children, "$prefix$uid/")];
    }

    return $names;
}

/** @return list<string> */
function settingsFormOrder(string $type): array
{
    $context = new FormContext(namespace: 'settings');

    return settingNames(
        app(FormResolver::class)->resolve(Fields::createField($type)->settingsForm($context), $context)->nodes,
    );
}

it('orders the Assets field settings the way Craft 5 did', function () {
    // Mirrors `_components/fieldtypes/Assets/settings.twig` in Craft 5.
    expect(settingsFormOrder(Assets::class))->toBe([
        'restrictLocation',
        // Composed onto one row under an “Asset Location” label. The prefix is
        // this test's tree notation — the setting names themselves are
        // unchanged, since Group children resolve at the parent namespace.
        'asset-location-settings/restricted-location/restrictedLocationSource',
        'asset-location-settings/restricted-location/restrictedLocationSubpath',
        'asset-location-settings/allowSubfolders',
        'asset-location-settings/asset-subfolder-settings/restrictedDefaultUploadSubpath',
        'asset-location-settings/sources',
        'asset-location-settings/default-upload-location/defaultUploadLocationSource',
        'asset-location-settings/default-upload-location/defaultUploadLocationSubpath',
        'asset-location-separator',
        'selectionCondition',
        'showUnpermittedVolumes',
        'showUnpermittedFiles',
        'restrictFiles',
        'asset-file-kind-settings/allowedKinds',
        'allowUploads',
        'minRelations',
        'maxRelations',
        'defaultPlacement',
        'viewMode',
        'selectionLabel',
        'asset-search-settings/showSearchInput',
        'validateRelatedElements',
        'preview-mode-separator',
        'previewMode',
        'relation-advanced-settings/allowSelfRelations',
    ]);
});

it('omits Maintain hierarchy and Branch Limit from the Assets field', function () {
    // Craft 5 never rendered the `maintainHierarchy` block for Assets, and kept
    // Branch Limit permanently hidden as a result.
    expect(settingsFormOrder(Assets::class))
        ->not->toContain('maintainHierarchy')
        ->not->toContain('branchLimit');
});

it('leaves the shared relation field settings order untouched', function () {
    // Entries still appends its own settings after the base Form (and so after
    // the Advanced group) — the same drift Assets had. Recorded as-is so this
    // refactor is provably order-neutral for every other relation field.
    expect(settingsFormOrder(Entries::class))->toBe([
        'sources',
        'selectionCondition',
        'maintainHierarchy',
        'minRelations',
        'maxRelations',
        'branchLimit',
        'defaultPlacement',
        'viewMode',
        'selectionLabel',
        'showSearchInput',
        'validateRelatedElements',
        'relation-advanced-settings/allowSelfRelations',
        'showUnpermittedSections',
        'showUnpermittedEntries',
    ]);
});
