<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;

it('projects the complete ordered user field settings surface', function () {
    $field = new class extends Users
    {
        public function getSourceOptions(): array
        {
            return [
                4 => ['label' => 'All users', 'value' => '*'],
                8 => ['label' => 'Authors', 'value' => 'group:authors'],
                12 => ['label' => 'Editors', 'value' => 'group:editors', 'data' => ['structure-id' => 7]],
            ];
        }
    };

    $definition = $field->getSettingsFormDefinition(false)?->toArray();

    expect(inputNames($definition))->toBe([
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
        'allowSelfRelations',
    ]);
    expect(input($definition, 'sources'))->toMatchArray([
        'type' => 'craft:checkbox-select-input',
        'props' => [
            'options' => [
                ['label' => 'All users', 'value' => '*'],
                ['label' => 'Authors', 'value' => 'group:authors'],
                ['label' => 'Editors', 'value' => 'group:editors'],
            ],
            'allOption' => '*',
        ],
    ]);
    expect(input($definition, 'sources')['props']['options'])->toBeArray()->toBeList();
    $selectionCondition = input($definition, 'selectionCondition');
    expect($selectionCondition['type'])->toBe('craft:element-condition-input')
        ->and($selectionCondition['props']['conditionClass'])->toBe(UserCondition::class)
        ->and($selectionCondition['props']['builderConfig'])->toMatchArray(['elementType' => User::class])
        ->and($selectionCondition)->not->toHaveKey('html');
    expect(field($definition, 'maintainHierarchy')['visibleWhen'])->toBe([
        'name' => 'sources',
        'operator' => 'equals',
        'value' => ['group:editors'],
    ]);
    expect(field($definition, 'branchLimit')['visibleWhen'])->toBe([
        'all' => [
            ['name' => 'sources', 'operator' => 'equals', 'value' => ['group:editors']],
            ['name' => 'maintainHierarchy', 'operator' => 'equals', 'value' => true],
        ],
    ]);
    expect(field($definition, 'minRelations')['visibleWhen'])->toBe([
        'any' => [
            ['name' => 'maintainHierarchy', 'operator' => 'notEquals', 'value' => true],
            ['name' => 'sources', 'operator' => 'notEquals', 'value' => ['group:editors']],
        ],
    ]);
});

it('preserves the no-source state without offering an invalid all-sources value', function () {
    $field = new class extends Users
    {
        public function getSourceOptions(): array
        {
            return [];
        }
    };

    $definition = $field->getSettingsFormDefinition(false)?->toArray();

    expect(input($definition, 'sources')['props'])->toBe([
        'options' => [],
    ]);
    expect(field($definition, 'sources')['props'])->toMatchArray([
        'readOnly' => true,
        'warning' => 'No sources exist yet.',
    ]);
});

it('projects the target-site toggle from the configured target site', function () {
    $field = new Users;
    $field->targetSiteId = 'secondary-site';

    expect($field->getSettings())->toMatchArray([
        'targetSiteId' => 'secondary-site',
        'useTargetSite' => true,
    ]);

    $field->targetSiteId = null;

    expect($field->getSettings()['useTargetSite'])->toBeFalse();
});

it('projects entry-specific settings in their existing order', function () {
    $field = new class extends Entries
    {
        public function getSourceOptions(): array
        {
            return [
                ['label' => 'News', 'value' => 'section:news', 'data' => ['structure-id' => 11]],
                ['label' => 'Pages', 'value' => 'section:pages'],
            ];
        }
    };

    $definition = $field->getSettingsFormDefinition(false)?->toArray();

    expect(inputNames($definition))->toBe([
        'sources',
        'selectionCondition',
        'showUnpermittedSections',
        'showUnpermittedEntries',
        'maintainHierarchy',
        'minRelations',
        'maxRelations',
        'branchLimit',
        'defaultPlacement',
        'viewMode',
        'selectionLabel',
        'showSearchInput',
        'validateRelatedElements',
        'allowSelfRelations',
    ]);
});

it('projects asset location, file, and relation settings in their existing order', function () {
    $field = new class extends Assets
    {
        public function getSourceOptions(): array
        {
            return [
                ['label' => 'Images', 'value' => 'volume:images'],
                ['label' => 'Documents', 'value' => 'volume:documents'],
            ];
        }

        public function getFileKindOptions(): array
        {
            return [
                ['label' => 'Image', 'value' => 'image'],
                ['label' => 'PDF', 'value' => 'pdf'],
            ];
        }
    };

    $definition = $field->getSettingsFormDefinition(false)?->toArray();

    expect(inputNames($definition))->toBe([
        'restrictLocation',
        'restrictedLocationSource',
        'restrictedLocationSubpath',
        'allowSubfolders',
        'restrictedDefaultUploadSubpath',
        'sources',
        'defaultUploadLocationSource',
        'defaultUploadLocationSubpath',
        'selectionCondition',
        'showUnpermittedVolumes',
        'showUnpermittedFiles',
        'restrictFiles',
        'allowedKinds',
        'allowUploads',
        'minRelations',
        'maxRelations',
        'defaultPlacement',
        'viewMode',
        'selectionLabel',
        'showSearchInput',
        'validateRelatedElements',
        'previewMode',
        'allowSelfRelations',
    ]);
    expect(field($definition, 'restrictedLocationSource')['visibleWhen'])->toBe([
        'name' => 'restrictLocation',
        'operator' => 'equals',
        'value' => true,
    ]);
    expect(field($definition, 'restrictedDefaultUploadSubpath')['visibleWhen'])->toBe([
        'all' => [
            ['name' => 'restrictLocation', 'operator' => 'equals', 'value' => true],
            ['name' => 'allowSubfolders', 'operator' => 'equals', 'value' => true],
        ],
    ]);
    expect(field($definition, 'allowedKinds')['visibleWhen'])->toBe([
        'name' => 'restrictFiles',
        'operator' => 'equals',
        'value' => true,
    ]);
});

it('marks every relational setting as read only without changing its input contract', function () {
    $field = new class extends Users
    {
        public function getSourceOptions(): array
        {
            return [['label' => 'All users', 'value' => '*']];
        }
    };

    $definition = $field->getSettingsFormDefinition(true)?->toArray();

    foreach (fields($definition) as $field) {
        expect($field['props']['readOnly'] ?? false)->toBeTrue();
    }
    expect(inputNames($definition))->toContain('sources', 'selectionLabel', 'validateRelatedElements');
});

function inputNames(?array $definition): array
{
    return array_map(
        fn (array $field): string => $field['children'][0]['name'],
        fields($definition),
    );
}

function fields(?array $definition): array
{
    return array_values(array_filter(
        $definition['elements'] ?? [],
        fn (array $element): bool => $element['type'] === 'craft:field',
    ));
}

function field(?array $definition, string $name): array
{
    return array_find(
        fields($definition),
        fn (array $field): bool => $field['children'][0]['name'] === $name,
    );
}

function input(?array $definition, string $name): array
{
    return field($definition, $name)['children'][0];
}
