<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\TableCells\TableCell;
use CraftCms\Cms\Field\TableCellTypes;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('needs authentication and admin changes for the routes', function (string $method, array $route, bool $requireAdminChanges) {
    auth()->logout();

    $this->$method(action($route))->assertUnauthorized();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    UserPermissions::saveUserPermissions(CraftCms\Cms\User\Models\User::first()->id, ['accessCp']);
    actingAs(User::find()->one());

    $this->$method(action($route))->assertForbidden();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => true]);
    actingAs(User::find()->one());

    if ($requireAdminChanges) {
        Cms::config()->allowAdminChanges(false);

        $this->$method(action($route))->assertForbidden();
    }
})->with([
    ['getJson', [FieldsController::class, 'index'], false],
    ['getJson', [FieldsController::class, 'edit'], false],
    ['postJson', [FieldsController::class, 'renderUi'], true],
    ['postJson', [FieldsController::class, 'renderFieldLayoutDesigner'], false],
    ['postJson', [FieldsController::class, 'renderGroupedEntryTypeManager'], true],
    ['postJson', [FieldsController::class, 'renderConditionBuilder'], true],
    ['postJson', [FieldsController::class, 'normalizeConditionBuilder'], true],
    ['postJson', [FieldsController::class, 'store'], true],
    ['postJson', [FieldsController::class, 'renderLayoutComponentSettings'], true],
    ['postJson', [FieldsController::class, 'applyLayoutTabSettings'], true],
    ['postJson', [FieldsController::class, 'applyLayoutElementSettings'], true],
    ['postJson', [FieldsController::class, 'renderCardPreview'], true],
]);

it('needs authentication and admin changes to delete', function () {
    auth()->logout();

    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    $this->deleteJson(action([FieldsController::class, 'destroy'], ['fieldId' => $field->id]))->assertUnauthorized();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => false]);
    UserPermissions::saveUserPermissions(CraftCms\Cms\User\Models\User::first()->id, ['accessCp']);
    actingAs(User::find()->one());

    $this->deleteJson(action([FieldsController::class, 'destroy'], ['fieldId' => $field->id]))->assertForbidden();

    CraftCms\Cms\User\Models\User::first()->update(['admin' => true]);
    actingAs(User::find()->one());

    Cms::config()->allowAdminChanges(false);

    $this->deleteJson(action([FieldsController::class, 'destroy'], ['fieldId' => $field->id]))->assertForbidden();
});

it('can render the index', function () {
    $this->get(action([FieldsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/fields/Index'));
});

it('can create a new field', function () {
    $this->get(action([FieldsController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('title', 'Create a new field')
            ->where('ui.values.fieldId', null)
            ->where('ui.values.type', PlainText::class)
            ->where('ui.values.oldType', PlainText::class)
            ->where('ui.values.translationMethod', 'none')
            ->where('ui.values.translationKeyFormat', '')
            ->has('supportedTranslationMethods')
            ->where('ui.refreshable', true)
            ->where('ui.nodes', function (Collection $nodes): bool {
                $settings = $nodes->firstWhere('uid', 'field-settings');

                return data_get($settings, 'props.dependsOn') === ['type']
                        && collect(data_get($settings, 'children'))->isNotEmpty();
            })
            ->where('submit.method', 'post')
            ->where('refreshUrl', action([FieldsController::class, 'renderUi'])));
});

it('preselects a requested field type when creating', function (mixed $type, string $expectedType) {
    $this->get(action([FieldsController::class, 'create'], ['type' => $type]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('ui.values.type', $expectedType));
})->with([
    'selectable type' => [RadioButtons::class, RadioButtons::class],
    'invalid class' => ['Not\\A\\Field', PlainText::class],
    'non-string' => [['array'], PlainText::class],
]);

it('404s when a field isn\'t found', function () {
    $this->get(action([FieldsController::class, 'edit'], ['fieldId' => 1]))
        ->assertNotFound();
});

it('can edit a field', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    $this->get(action([FieldsController::class, 'edit'], ['fieldId' => $field->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('title', 'My plaintext field')
            ->where('ui.values.fieldId', $field->id)
            ->where('ui.values.name', 'My plaintext field')
            ->where('ui.values.handle', 'plainText')
            ->where('ui.values.type', PlainText::class)
            ->where('details', fn ($value) => is_string($value) && $value !== '')
            ->has('ui.values.settings')
            ->has('ui.nodes'));
});

it('renders the edit screen read-only without admin changes', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    Cms::config()->allowAdminChanges(false);

    $this->get(sprintf('/%s/settings/fields/edit/%d', Cms::config()->cpTrigger, $field->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('refreshUrl', null)
            ->where('ui.refreshable', false)
            ->where('ui.nodes', function (Collection $nodes): bool {
                $controls = $nodes->pluck('control')->filter();

                return $controls->isNotEmpty()
                    && $controls->every(fn (array $control): bool => $control['mode'] === 'readOnly');
            }));
});

it('serves the Form page to slideout requests', function (?callable $setUp, bool $hasSidebar) {
    $fieldId = $setUp ? $setUp()->id : null;

    $response = $this->getJson(
        action([FieldsController::class, 'edit'], array_filter(['fieldId' => $fieldId])),
        ['X-Craft-Container-Id' => 'slideout'],
    )
        ->assertOk()
        ->assertJsonPath('inertiaPage', 'settings/fields/Edit')
        ->assertJsonPath('inertiaProps.ui.values.fieldId', $fieldId)
        ->assertJsonPath('inertiaProps.submit.method', 'post')
        ->assertJsonPath('formAttributes.action', action([FieldsController::class, 'store']))
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('inertiaProps.ui.nodes')
            ->etc());

    expect(is_string($response->json('sidebar')))->toBe($hasSidebar);
})->with([
    'new field' => [null, false],
    'existing field' => [function () {
        Fields::saveField($field = Fields::createField([
            'type' => PlainText::class,
            'name' => 'My plaintext field',
            'handle' => 'plainText',
        ]));

        return $field;
    }, true],
]);

it('limits slideout field types to multi-instance fields when requested', function () {
    $this->getJson(
        action([FieldsController::class, 'edit'], ['multiInstanceTypesOnly' => 1]),
        ['X-Craft-Container-Id' => 'slideout'],
    )
        ->assertOk()
        ->assertJsonPath('inertiaProps.refreshUrl', action([
            FieldsController::class,
            'renderUi',
        ], ['multiInstanceTypesOnly' => 1]))
        ->assertJsonPath('inertiaProps.ui.nodes', function (array $nodes): bool {
            $typeField = collect($nodes)->first(
                fn (array $node): bool => data_get($node, 'control.path') === ['type'],
            );
            $options = collect(data_get($typeField, 'control.props.options'));

            return $options->isNotEmpty()
                && $options->pluck('value')->every(fn (string $type): bool => $type::isMultiInstance());
        });
});

it('refreshes the complete field form when its type changes', function () {
    $this->postJson(action([FieldsController::class, 'renderUi']), [
        'values' => [
            'fieldId' => null,
            'oldType' => PlainText::class,
            'type' => RadioButtons::class,
            'name' => 'Options',
            'handle' => 'options',
            'searchable' => true,
            'translationMethod' => 'custom',
            'settings' => [],
        ],
        'scope' => [],
    ])
        ->assertOk()
        ->assertJsonPath('ui.scope', [])
        ->assertJsonPath('ui.values.type', RadioButtons::class)
        ->assertJsonPath('ui.values.oldType', RadioButtons::class)
        ->assertJsonPath('ui.values.name', 'Options')
        ->assertJsonPath('ui.values.searchable', true)
        ->assertJsonPath('ui.refreshable', true);
});

it('uses the saved field type as the compatibility baseline after refresh', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    $response = $this->postJson(action([FieldsController::class, 'renderUi']), [
        'values' => [
            'fieldId' => $field->id,
            'oldType' => PlainText::class,
            'type' => Matrix::class,
            'name' => $field->name,
            'handle' => $field->handle,
            'settings' => [],
        ],
        'scope' => [],
    ])->assertOk();
    $typeControl = collect($response->json('ui.nodes'))
        ->first(fn (array $node): bool => data_get($node, 'control.path') === ['type'])['control'];
    $plainTextOption = collect($typeControl['props']['options'])
        ->firstWhere('value', PlainText::class);

    expect($plainTextOption['label'])->toBe(PlainText::displayName());
});

it('renders composite field settings Controls', function (string $type, string $component, string $action, string $htmlFragment) {
    $field = Fields::createField($type);
    $context = new UiContext(namespace: 'settings');
    $payload = app(UiResolver::class)->resolve($field->settingsUi($context), $context);
    $control = collect($payload->nodes)
        ->first(fn ($node) => $node->control?->component === $component)
        ->control;

    $data = [
        'value' => data_get($payload->values, implode('.', $control->path)),
        'name' => 'settings['.end($control->path).']',
        'disabled' => false,
        ...$control->props,
    ];

    $this->postJson(action([FieldsController::class, $action]), $data)
        ->assertOk()
        ->assertJsonPath('html', fn (string $html): bool => str_contains($html, $htmlFragment));
})->with([
    'field layout designer' => [ContentBlock::class, 'craft:field-layout-designer', 'renderFieldLayoutDesigner', 'field-layout'],
    'grouped entry type manager' => [Matrix::class, 'craft:grouped-entry-type-manager', 'renderGroupedEntryTypeManager', 'craft-entry-type-manager'],
    'condition builder' => [Entries::class, 'craft:condition-builder', 'renderConditionBuilder', 'condition-container'],
]);

it('renders a disabled field layout designer when admin changes are disabled', function () {
    Cms::config()->allowAdminChanges(false);

    $this->postJson(action([FieldsController::class, 'renderFieldLayoutDesigner']), [
        'value' => [],
        'elementType' => Entry::class,
        'name' => 'fieldLayout',
        'disabled' => false,
        'customizableTabs' => true,
        'withGeneratedFields' => true,
        'withCardViewDesigner' => true,
    ])->assertOk();
});

it('renders field layout and generated field values at their root submission paths', function () {
    $response = $this->postJson(action([FieldsController::class, 'renderFieldLayoutDesigner']), [
        'value' => [],
        'elementType' => Entry::class,
        'name' => 'fieldLayout',
        'disabled' => false,
        'customizableTabs' => true,
        'withGeneratedFields' => true,
        'withCardViewDesigner' => true,
    ])->assertOk();
    $crawler = new Crawler($response->json('html'));

    $generatedFields = json_decode($crawler->filter('craft-generated-fields-table')->attr('data-payload'), true, flags: JSON_THROW_ON_ERROR);

    expect($crawler->filter('[data-config-input][name="fieldLayout"]'))->toHaveCount(1)
        ->and($generatedFields['nodes'][0]['control']['path'])->toBe(['generatedFields']);
});

it('rejects non-condition classes from the condition builder endpoint', function () {
    $this->postJson(action([FieldsController::class, 'renderConditionBuilder']), [
        'value' => [],
        'conditionClass' => PlainText::class,
        'queryParams' => [],
        'forProjectConfig' => false,
        'name' => 'settings[selectionCondition]',
        'disabled' => false,
    ])->assertUnprocessable()->assertJsonValidationErrors('conditionClass');
});

it('renders a condition builder without query params', function () {
    $this->postJson(action([FieldsController::class, 'renderConditionBuilder']), [
        'value' => [],
        'conditionClass' => ElementCondition::class,
        'queryParams' => [],
        'forProjectConfig' => false,
        'name' => 'settings[condition]',
        'disabled' => false,
    ])
        ->assertOk()
        ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'condition-container'))
        ->assertJsonPath('html', fn (string $html): bool => str_contains($html, '<craft-condition-builder') && str_contains($html, 'data-name="settings[condition]"'));
});

it('normalizes namespaced condition builder values', function () {
    $this->postJson(action([FieldsController::class, 'normalizeConditionBuilder']), [
        'serialized' => http_build_query([
            'settings' => ['selectionCondition' => ['conditionRules' => [['operator' => 'and']]]],
        ]),
        'path' => ['settings', 'selectionCondition'],
    ])->assertOk()->assertJsonPath('value.conditionRules.0.operator', 'and');
});

it('can save a new field', function () {
    $currentCount = FieldModel::count();

    $response = $this->postJson(
        action([FieldsController::class, 'store']),
        [
            'type' => PlainText::class,
            'name' => 'My plaintext field',
            'handle' => 'plainText',
        ],
        ['Accept' => 'text/html', 'X-Inertia' => 'true'],
    );

    expect(FieldModel::count())->toBe($currentCount + 1);
    tap(FieldModel::query()->latest('id')->firstOrFail(), function (FieldModel $field) {
        expect($field->name)->toBe('My plaintext field');
        expect($field->handle)->toBe('plainText');
        expect($field->type)->toBe(PlainText::class);
    });

    $response->assertRedirect(Fields::getFieldByHandle('plainText')->getCpEditUrl());
});

it('can save a new field with settings posted as a url-encoded string', function () {
    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainTextViaString',
        'typeSettings' => http_build_query([
            'types' => [
                'CraftCms-Cms-Field-PlainText' => [
                    'placeholder' => 'Type something…',
                    'multiline' => '1',
                ],
            ],
        ]),
    ])->assertOk();

    tap(FieldModel::query()->latest('id')->firstOrFail(), function (FieldModel $field) {
        expect($field->handle)->toBe('plainTextViaString');
        expect($field->settings['placeholder'])->toBe('Type something…');
        expect($field->settings['multiline'])->toBeTrue();
    });
});

it('saves only the selected Matrix site destination while keeping the URI format', function () {
    $site = Site::firstOrFail();
    $entryType = EntryType::factory()->withFieldLayout()->create();
    $data = [
        'type' => Matrix::class,
        'name' => 'Routed entries',
        'handle' => 'routedEntries',
        'settings' => [
            'entryTypes' => [$entryType->id],
            'siteSettings' => [$site->uid => [
                'uriFormat' => 'nested/{slug}',
                'routeType' => 'route',
                'route' => ' entries.nested ',
            ]],
        ],
    ];

    $this->postJson(action([FieldsController::class, 'store']), $data)->assertSuccessful();

    $field = FieldModel::where('handle', 'routedEntries')->firstOrFail();
    expect($field->settings['siteSettings'][$site->uid])->toBe([
        'uriFormat' => 'nested/{slug}',
        'route' => 'entries.nested',
    ]);

    $data['fieldId'] = $field->id;
    $data['settings']['siteSettings'][$site->uid]['routeType'] = 'template';
    $data['settings']['siteSettings'][$site->uid]['route'] = 'entries/nested';
    $this->postJson(action([FieldsController::class, 'store']), $data)->assertSuccessful();

    expect($field->fresh()->settings['siteSettings'][$site->uid])->toBe([
        'uriFormat' => 'nested/{slug}',
        'template' => 'entries/nested',
    ]);
});

it('saves changed Form groups without resetting untouched settings', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
        'placeholder' => 'Before',
        'initialRows' => 8,
    ]));

    $this->postJson(action([FieldsController::class, 'store']), [
        'fieldId' => $field->id,
        'type' => PlainText::class,
        'name' => $field->name,
        'handle' => $field->handle,
        'settings' => ['placeholder' => 'After'],
    ])->assertOk();

    $saved = Fields::getFieldById($field->id);

    expect($saved->placeholder)->toBe('After')
        ->and($saved->initialRows)->toBe(8);
});

it('saves complete atomic Form groups', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
        'charLimit' => 10,
    ]));

    $this->postJson(action([FieldsController::class, 'store']), [
        'fieldId' => $field->id,
        'type' => PlainText::class,
        'name' => $field->name,
        'handle' => $field->handle,
        'settings' => [
            'fieldLimit' => 25,
            'limitUnit' => 'bytes',
        ],
    ])->assertOk();

    $saved = Fields::getFieldById($field->id);

    expect($saved->charLimit)->toBeNull()
        ->and($saved->byteLimit)->toBe(25);
});

it('returns Form setting validation errors at their submitted paths', function () {
    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
        'settings' => ['initialRows' => 0],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('settings.initialRows');
});

it('keeps host and Form validation errors at their submitted paths', function () {
    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => '',
        'handle' => '',
        'settings' => ['initialRows' => 0],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'handle', 'settings.initialRows'])
        ->assertJsonMissingValidationErrors(['settings.name', 'settings.handle']);
});

it('can delete a field', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    $currentCount = FieldModel::count();

    $this->deleteJson(action([FieldsController::class, 'destroy'], ['fieldId' => $field->id]))
        ->assertOk();

    expect(FieldModel::count())->toBe($currentCount - 1);
});

it('rejects invalid selection conditions before saving a relation field', function () {
    $uid = (string) Str::uuid();
    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => Entries::class,
        'name' => 'Related',
        'handle' => 'related',
        'settings' => ['selectionCondition' => [
            'class' => ElementCondition::class,
            'elementType' => Entry::class,
            'conditionRules' => [['class' => TitleConditionRule::class, 'uid' => $uid, 'operator' => 'invalid']],
        ]],
    ])->assertJsonValidationErrorFor("settings.selectionCondition.$uid.operator");

    expect(FieldModel::where('handle', 'related')->exists())->toBeFalse();
});

it('rejects invalid table cell configuration before resolving or saving settings', function (string $action, string $errorPath, array $column) {
    $values = [
        'type' => Table::class,
        'name' => 'Table',
        'handle' => 'table',
        'settings' => ['columns' => [
            'col1' => ['heading' => 'Value', 'handle' => 'value', ...$column],
        ]],
    ];

    $this->postJson(action([FieldsController::class, $action]), $action === 'renderUi'
        ? ['values' => $values, 'scope' => []]
        : $values)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errorPath);

    expect(FieldModel::where('handle', 'table')->exists())->toBeFalse();
})->with([
    'save unavailable type' => ['store', 'settings.columns.col1.type', ['type' => 'Unavailable\\TableCell']],
    'refresh unavailable type' => ['renderUi', 'values.settings.columns.col1.type', ['type' => 'Unavailable\\TableCell']],
    'save invalid settings' => ['store', 'settings.columns.col1.settings', ['type' => 'singleline', 'settings' => 'invalid']],
    'refresh invalid settings' => ['renderUi', 'values.settings.columns.col1.settings', ['type' => 'singleline', 'settings' => 'invalid']],
]);

it('validates plugin cell settings before saving a table field', function (array $settings, bool $valid, string $errorPath) {
    app(TableCellTypes::class)->register(FieldSettingsTableCell::class);
    $response = $this->postJson(action([FieldsController::class, 'store']), [
        'type' => Table::class,
        'name' => 'Table',
        'handle' => 'table',
        'settings' => ['columns' => [
            'col1' => ['heading' => 'Value', 'handle' => 'value', 'type' => FieldSettingsTableCell::class, ...$settings],
        ]],
    ]);

    if (! $valid) {
        $response->assertUnprocessable()->assertJsonValidationErrors($errorPath);
        expect(FieldModel::where('handle', 'table')->exists())->toBeFalse();

        return;
    }

    $response->assertOk();
    $field = Fields::getFieldByHandle('table');
    expect($field->cellType($field->columns['col1'])->prefix)->toBe('Valid');
})->with([
    'invalid flat settings' => [['prefix' => 'a'], false, 'settings.columns.col1.prefix'],
    'invalid nested settings' => [['settings' => ['prefix' => 'a']], false, 'settings.columns.col1.settings.prefix'],
    'valid nested settings' => [['settings' => ['prefix' => 'Valid']], true, ''],
    'flat settings override nested settings' => [['prefix' => 'Valid', 'settings' => ['prefix' => 'a']], true, ''],
]);

it('refreshes a legacy field settings island without returning the outer field metadata form', function () {
    $response = $this->postJson(action([FieldsController::class, 'renderUi']), [
        'values' => [
            'type' => Table::class,
            'name' => 'Outer field name',
            'settings' => [
                'columns' => ['col1' => ['heading' => 'Status', 'handle' => 'status', 'type' => 'singleline']],
                'defaults' => [['col1' => 'Draft']],
            ],
        ],
        'scope' => [],
        'settingsOnly' => true,
    ])->assertOk()
        ->assertJsonPath('ui.scope', ['settings'])
        ->assertJsonPath('ui.values.settings.columns.col1.heading', 'Status')
        ->assertJsonPath('ui.values.settings.defaults.0.col1', 'Draft');

    expect($response->json('ui.values'))->not->toHaveKey('name');
});

it('retains retired cell types only in unchanged persisted columns', function (string $action, string $scenario, bool $allowed) {
    RetiredFieldSettingsTableCell::$selectable = true;
    app(TableCellTypes::class)->register(RetiredFieldSettingsTableCell::class);
    $columns = ['col1' => [
        'heading' => 'Value',
        'handle' => 'value',
        'width' => '',
        'type' => RetiredFieldSettingsTableCell::class,
        'settings' => ['prefix' => 'Original'],
    ]];
    expect(Fields::saveField($field = Fields::createField([
        'type' => Table::class,
        'name' => 'Original table',
        'handle' => 'table',
        'columns' => $columns,
    ])))->toBeTrue();
    RetiredFieldSettingsTableCell::$selectable = false;

    $submitted = $columns;
    if ($scenario === 'changed') {
        $submitted['col1']['settings']['prefix'] = 'Changed';
    } elseif ($scenario === 'added') {
        $submitted['col2'] = [...$columns['col1'], 'handle' => 'anotherValue'];
    }
    $values = [
        'type' => Table::class,
        'name' => 'Renamed table',
        'handle' => $scenario === 'new' ? 'newTable' : 'table',
        'settings' => ['columns' => $submitted],
    ];
    if ($scenario !== 'new') {
        $values['fieldId'] = $field->id;
    }

    try {
        $response = $this->postJson(action([FieldsController::class, $action]), $action === 'renderUi'
            ? ['values' => $values, 'scope' => []]
            : $values);
        if (! $allowed) {
            $columnId = $scenario === 'added' ? 'col2' : 'col1';
            $prefix = $action === 'renderUi' ? 'values.settings' : 'settings';
            $response->assertUnprocessable()->assertJsonValidationErrors("{$prefix}.columns.{$columnId}.type");
            expect(Fields::getFieldById($field->id)->columns)->toBe($columns)
                ->and(FieldModel::where('handle', 'newTable')->exists())->toBeFalse();

            return;
        }

        $response->assertOk();
        if ($action === 'renderUi') {
            $response->assertJsonPath('ui.values.settings.columns', $columns);
        } else {
            $saved = Fields::getFieldById($field->id);
            expect($saved->name)->toBe('Renamed table')->and($saved->columns)->toBe($columns);
        }
    } finally {
        RetiredFieldSettingsTableCell::$selectable = true;
    }
})->with(['store', 'renderUi'])->with([
    'unchanged existing column' => ['unchanged', true],
    'changed existing column' => ['changed', false],
    'added column' => ['added', false],
    'new field' => ['new', false],
]);

it('preserves unavailable table configuration while saving unrelated field metadata', function () {
    $cellTypes = app(TableCellTypes::class);
    $cellTypes->register(FieldSettingsTableCell::class);
    $columns = [
        'col1' => [
            'heading' => 'Value',
            'handle' => 'value',
            'type' => FieldSettingsTableCell::class,
            'settings' => ['prefix' => 'Original'],
        ],
    ];
    $defaults = [['col1' => 'Saved value']];

    Fields::saveField($field = Fields::createField([
        'type' => Table::class,
        'name' => 'Original table',
        'handle' => 'table',
        'columns' => $columns,
        'defaults' => $defaults,
    ]));
    $cellTypes->remove(FieldSettingsTableCell::class);

    $this->postJson(action([FieldsController::class, 'store']), [
        'fieldId' => $field->id,
        'type' => Table::class,
        'name' => 'Renamed table',
        'handle' => 'table',
        'settings' => [
            'columns' => ['col1' => ['heading' => 'Changed', 'handle' => 'value', 'type' => 'singleline']],
            'defaults' => [],
        ],
    ])->assertOk();

    $saved = Fields::getFieldById($field->id);

    expect($saved->name)->toBe('Renamed table')
        ->and($saved->columns)->toBe($columns)
        ->and($saved->defaults)->toBe($defaults);
});

class FieldSettingsTableCell extends TableCell
{
    public string $prefix = '';

    public function getRules(): array
    {
        return ['prefix' => ['nullable', 'string', 'min:3']];
    }

    public static function displayName(): string
    {
        return 'Field settings cell';
    }
}

class RetiredFieldSettingsTableCell extends FieldSettingsTableCell
{
    public static bool $selectable = true;

    public static function isSelectable(): bool
    {
        return self::$selectable;
    }
}
