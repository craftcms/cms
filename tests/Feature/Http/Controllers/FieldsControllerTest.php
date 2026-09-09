<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
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
    ['postJson', [FieldsController::class, 'renderForm'], true],
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
            ->where('form.values.fieldId', null)
            ->where('form.values.type', PlainText::class)
            ->where('form.values.oldType', PlainText::class)
            ->where('form.values.translationMethod', 'none')
            ->where('form.values.translationKeyFormat', '')
            ->has('supportedTranslationMethods')
            ->where('form.refreshable', true)
            ->where('form.nodes', function (Collection $nodes): bool {
                $settings = $nodes->firstWhere('uid', 'field-settings');

                return data_get($settings, 'props.dependsOn') === ['type']
                        && collect(data_get($settings, 'children'))->isNotEmpty();
            })
            ->where('submit.method', 'post')
            ->where('refreshUrl', action([FieldsController::class, 'renderForm'])));
});

it('preselects a requested field type when creating', function (mixed $type, string $expectedType) {
    $this->get(action([FieldsController::class, 'create'], ['type' => $type]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('form.values.type', $expectedType));
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
            ->where('form.values.fieldId', $field->id)
            ->where('form.values.name', 'My plaintext field')
            ->where('form.values.handle', 'plainText')
            ->where('form.values.type', PlainText::class)
            ->where('details', fn ($value) => is_string($value) && $value !== '')
            ->has('form.values.settings')
            ->has('form.nodes'));
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
            ->where('form.refreshable', false)
            ->where('form.nodes', function (Collection $nodes): bool {
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
        ->assertJsonPath('inertiaProps.form.values.fieldId', $fieldId)
        ->assertJsonPath('inertiaProps.submit.method', 'post')
        ->assertJsonPath('formAttributes.action', action([FieldsController::class, 'store']))
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('inertiaProps.form.nodes')
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
            'renderForm',
        ], ['multiInstanceTypesOnly' => 1]))
        ->assertJsonPath('inertiaProps.form.nodes', function (array $nodes): bool {
            $typeField = collect($nodes)->first(
                fn (array $node): bool => data_get($node, 'control.path') === ['type'],
            );
            $options = collect(data_get($typeField, 'control.props.options'));

            return $options->isNotEmpty()
                && $options->pluck('value')->every(fn (string $type): bool => $type::isMultiInstance());
        });
});

it('refreshes the complete field form when its type changes', function () {
    $this->postJson(action([FieldsController::class, 'renderForm']), [
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
        ->assertJsonPath('form.scope', [])
        ->assertJsonPath('form.values.type', RadioButtons::class)
        ->assertJsonPath('form.values.oldType', RadioButtons::class)
        ->assertJsonPath('form.values.name', 'Options')
        ->assertJsonPath('form.values.searchable', true)
        ->assertJsonPath('form.refreshable', true);
});

it('uses the saved field type as the compatibility baseline after refresh', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    $response = $this->postJson(action([FieldsController::class, 'renderForm']), [
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
    $typeControl = collect($response->json('form.nodes'))
        ->first(fn (array $node): bool => data_get($node, 'control.path') === ['type'])['control'];
    $plainTextOption = collect($typeControl['props']['options'])
        ->firstWhere('value', PlainText::class);

    expect($plainTextOption['label'])->toBe(PlainText::displayName());
});

it('renders composite field settings Controls', function (string $type, string $component, string $action, string $htmlFragment) {
    $field = Fields::createField($type);
    $context = new FormContext(namespace: 'settings');
    $payload = app(FormResolver::class)->resolve($field->settingsForm($context), $context);
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

it('renders root field layout input names that can be expanded as post data', function () {
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

    expect($crawler->filter('[data-config-input][name="fieldLayout"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-generated-fields-table[name="generatedFields"]'))->toHaveCount(1);
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
        ->assertJsonPath('bodyHtml', fn (string $html): bool => str_contains($html, 'htmx.min.js') && str_contains($html, 'ConditionBuilder.js'));
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

    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ])->assertOk();

    expect(FieldModel::count())->toBe($currentCount + 1);
    tap(FieldModel::query()->latest('id')->firstOrFail(), function (FieldModel $field) {
        expect($field->name)->toBe('My plaintext field');
        expect($field->handle)->toBe('plainText');
        expect($field->type)->toBe(PlainText::class);
    });
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
