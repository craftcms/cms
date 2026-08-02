<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\Site\Models\Site;

it('projects content block settings through the existing field layout designer', function () {
    $layout = new FieldLayout([
        'uid' => 'content-layout',
        'type' => CraftCms\Cms\Field\Elements\ContentBlock::class,
    ]);
    $layout->setTabs([
        new FieldLayoutTab([
            'uid' => 'content-tab',
            'name' => 'Content',
            'layout' => $layout,
            'elements' => [new HorizontalRule(['uid' => 'divider'])],
        ]),
    ]);
    $field = new ContentBlock([
        'fieldLayout' => $layout,
        'viewMode' => 'inline',
    ]);

    $definition = $field->getSettingsForm(true)?->toArray();
    $inputs = nestedContentInputs($definition);

    expect(array_keys($inputs))->toBe([
        'fieldLayouts.content-layout',
        'viewMode',
    ])->and($inputs['fieldLayouts.content-layout']['type'])->toBe('craft:field-layout-designer')
        ->and($inputs['fieldLayouts.content-layout']['props']['designerHtml'])->toContain(
            '<craft-field-layout-designer',
            'content-tab',
            'divider',
        )->and($inputs['fieldLayouts.content-layout']['props']['generatedFieldsHtml'])->toContain(
            '<craft-generated-fields-table',
        )
        ->and($inputs['viewMode']['props']['options'])->toBe([
            ['label' => 'Grouped', 'value' => 'grouped'],
            ['label' => 'In a pane', 'value' => 'pane'],
            ['label' => 'Inline', 'value' => 'inline'],
        ])
        ->and($definition)->not->toHaveKey('fieldLayout');
});

it('projects the complete matrix settings surface with complex values kept outside the definition', function () {
    $article = EntryType::factory()->create([
        'name' => 'Article',
        'handle' => 'article',
    ]);
    $page = EntryType::factory()->create([
        'name' => 'Page',
        'handle' => 'page',
    ]);
    $secondarySite = Site::factory()->create(['name' => 'French']);
    $entryTypes = app(EntryTypes::class);
    $field = new Matrix([
        'entryTypes' => [
            $entryTypes->getEntryTypeById($article->id),
            $entryTypes->getEntryTypeById($page->id),
        ],
        'minEntries' => 1,
        'maxEntries' => 5,
        'viewMode' => Matrix::VIEW_MODE_INDEX,
        'includeTableView' => true,
        'defaultTableColumns' => ['title', 'status'],
        'defaultIndexViewMode' => 'table',
        'pageSize' => 20,
        'createButtonLabel' => 'Add content',
    ]);

    $definition = $field->getSettingsForm(false)?->toArray();
    $inputs = nestedContentInputs($definition);

    expect(array_keys($inputs))->toBe([
        'entryTypes',
        'propagationMethod',
        'propagationKeyFormat',
        'siteSettings',
        'minEntries',
        'maxEntries',
        'enableVersioning',
        'viewMode',
        'includeTableView',
        'defaultTableColumns',
        'defaultIndexViewMode',
        'pageSize',
        'createButtonLabel',
    ])->and($inputs['entryTypes']['type'])->toBe('craft:object-select-input')
        ->and(array_column($inputs['entryTypes']['props']['options'], 'key'))->toContain($article->uid, $page->uid)
        ->and($inputs['entryTypes']['props']['identityKey'])->toBe('uid')
        ->and($inputs['siteSettings']['type'])->toBe('craft:editable-table-input')
        ->and($inputs['siteSettings']['props']['columns'])->toBe([
            ['key' => 'uriFormat', 'label' => 'Entry URI Format', 'type' => 'text', 'placeholder' => 'Leave blank if entries don’t have URLs', 'code' => true],
            ['key' => 'template', 'label' => 'Template', 'type' => 'text', 'code' => true],
        ])
        ->and(array_column($inputs['siteSettings']['props']['fixedRows'], 'key'))->toContain($secondarySite->uid)
        ->and($inputs['siteSettings']['props']['keyed'])->toBeTrue()
        ->and(nestedContentField($definition, 'propagationKeyFormat')['visibleWhen'])->toBe([
            'name' => 'propagationMethod',
            'operator' => 'equals',
            'value' => 'custom',
        ])
        ->and(nestedContentField($definition, 'includeTableView')['visibleWhen'])->toBe([
            'name' => 'viewMode',
            'operator' => 'equals',
            'value' => Matrix::VIEW_MODE_INDEX,
        ])
        ->and(nestedContentField($definition, 'defaultTableColumns')['visibleWhen'])->toBe([
            'all' => [
                ['name' => 'viewMode', 'operator' => 'equals', 'value' => Matrix::VIEW_MODE_INDEX],
                ['name' => 'includeTableView', 'operator' => 'equals', 'value' => true],
            ],
        ])
        ->and(nestedContentField($definition, 'defaultIndexViewMode')['visibleWhen'])->toBe([
            'all' => [
                ['name' => 'viewMode', 'operator' => 'equals', 'value' => Matrix::VIEW_MODE_INDEX],
                ['name' => 'includeTableView', 'operator' => 'equals', 'value' => true],
            ],
        ])
        ->and(json_encode($definition, JSON_THROW_ON_ERROR))->not->toContain('Add content')
        ->and($field->getSettings())->toMatchArray([
            'entryTypes' => [
                ['uid' => $article->uid],
                ['uid' => $page->uid],
            ],
            'minEntries' => 1,
            'maxEntries' => 5,
            'defaultTableColumns' => ['title', 'status'],
            'createButtonLabel' => 'Add content',
        ]);
});

function nestedContentInputs(?array $definition): array
{
    $inputs = [];

    foreach (nestedContentFields($definition) as $field) {
        $input = $field['children'][0];
        $inputs[$input['name']] = $input;
    }

    return $inputs;
}

function nestedContentFields(?array $definition): array
{
    return array_values(array_filter(
        $definition['elements'] ?? [],
        fn (array $element): bool => $element['type'] === 'craft:field',
    ));
}

function nestedContentField(?array $definition, string $name): array
{
    return array_find(
        nestedContentFields($definition),
        fn (array $field): bool => $field['children'][0]['name'] === $name,
    );
}
