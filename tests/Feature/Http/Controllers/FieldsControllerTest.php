<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;

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
    ['postJson', [FieldsController::class, 'renderSettings'], true],
    ['postJson', [FieldsController::class, 'store'], true],
    ['postJson', [FieldsController::class, 'renderLayoutComponentSettings'], true],
    ['postJson', [FieldsController::class, 'applyLayoutTabSettings'], true],
    ['postJson', [FieldsController::class, 'applyLayoutElementSettings'], true],
    ['postJson', [FieldsController::class, 'renderCardPreview'], true],
    ['getJson', [FieldsController::class, 'tableData'], false],
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
    $typeId = Html::id(PlainText::class);

    $this->get(action([FieldsController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('title', 'Create a new field')
            ->where('brandNew', true)
            ->where('field.type', PlainText::class)
            ->where('isMultiSite', fn ($value) => is_bool($value))
            ->has('fieldTypeOptions')
            ->has('supportedTranslationMethods')
            ->has('translationMethodOptions', 5)
            ->where('settingsInputNamespace', "types[{$typeId}]")
            ->where('settingsBindingScope', "types.{$typeId}")
            ->where('settingsValues.types.'.$typeId.'.uiMode', 'normal')
            ->where('settingsErrors', [])
            ->has('settingsDefinition.elements')
            ->missing('settings'));
});

it('preselects a requested field type when creating', function (mixed $type, string $expectedType) {
    $this->get(action([FieldsController::class, 'create'], ['type' => $type]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('field.type', $expectedType));
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
            ->where('brandNew', false)
            ->where('readOnly', false)
            ->where('field.id', $field->id)
            ->where('field.name', 'My plaintext field')
            ->where('field.handle', 'plainText')
            ->where('field.type', PlainText::class)
            ->where('metadataHtml', fn ($value) => is_string($value) && $value !== '')
            ->where('missingFieldPlaceholder', null)
            ->has('settingsDefinition.elements'));
});

it('renders the edit screen read-only without admin changes', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
        'settings' => [
            'placeholder' => 'Existing placeholder',
            'multiline' => true,
        ],
    ]));

    Cms::config()->allowAdminChanges(false);
    $typeId = Html::id(PlainText::class);

    $this->get(sprintf('/%s/settings/fields/edit/%d', Cms::config()->cpTrigger, $field->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('readOnly', true)
            ->where('settingsInputNamespace', "types[{$typeId}]")
            ->where('settingsBindingScope', "types.{$typeId}")
            ->where('settingsValues.types.'.$typeId.'.placeholder', 'Existing placeholder')
            ->where('settingsValues.types.'.$typeId.'.multiline', true)
            ->where('settingsErrors', [])
            ->where('settingsDefinition', fn (Collection $definition): bool => array_all(
                $definition->get('elements'),
                fn (array $element): bool => ($element['props']['readOnly'] ?? false) === true,
            )));
});

it('serves the native field editor to slideout requests', function (?callable $setUp) {
    $fieldId = $setUp ? $setUp()->id : null;

    $this->getJson(
        action([FieldsController::class, 'edit'], array_filter(['fieldId' => $fieldId])),
        ['X-Craft-Container-Id' => 'slideout'],
    )
        ->assertOk()
        ->assertJsonPath('action', 'fields/save-field')
        ->assertJsonPath('inertiaPage', 'settings/fields/Edit')
        ->assertJsonPath('inertiaProps.embedded', true)
        ->assertJsonMissingPath('settingsHtml')
        ->assertJson(fn (AssertableJson $json) => $json
            ->whereType('inertiaProps.settingsDefinition.elements', 'array')
            ->etc());
})->with([
    'new field' => [null],
    'existing field' => [function () {
        Fields::saveField($field = Fields::createField([
            'type' => PlainText::class,
            'name' => 'My plaintext field',
            'handle' => 'plainText',
        ]));

        return $field;
    }],
]);

it('can render the settings of a field', function () {
    $typeId = Html::id(PlainText::class);

    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => PlainText::class,
        'settings' => [],
    ])->assertOk()
        ->assertJsonPath('inputNamespace', "types[{$typeId}]")
        ->assertJsonPath('bindingScope', "types.{$typeId}")
        ->assertJsonPath("values.types.{$typeId}.uiMode", 'normal')
        ->assertJsonPath('errors', [])
        ->assertJsonFragment(['type' => 'craft:select-input', 'name' => 'uiMode'])
        ->assertJsonMissingPath('settingsHtml');
});

it('rejects unsupported settings protocols', function () {
    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => PlainText::class,
        'settings' => 42,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('settings');
});

it('always serves the native settings contract', function () {
    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => PlainText::class,
    ])->assertOk()
        ->assertJsonFragment(['type' => 'craft:select-input', 'name' => 'uiMode'])
        ->assertJsonMissingPath('settingsHtml');
});

it('preserves values between rendering settings', function () {
    $label = Str::random();

    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => RadioButtons::class,
        'oldType' => MultiSelect::class,
        'settings' => [
            'options' => [
                ['label' => $label, 'value' => 'value', 'icon' => '', 'color' => '', 'default' => ''],
            ],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('values.types.'.Html::id(RadioButtons::class).'.options.0.label', $label);
});

it('uses native host values when rendering replacement settings', function () {
    $label = Str::random();

    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => RadioButtons::class,
        'oldType' => MultiSelect::class,
        'settings' => [
            'options' => [
                ['label' => $label, 'value' => 'value', 'icon' => '', 'color' => '', 'default' => ''],
            ],
        ],
        'typeSettings' => http_build_query([
            'types' => [
                Html::id(MultiSelect::class) => [
                    'options' => [
                        ['label' => 'Ignored compatibility value', 'value' => 'value', 'icon' => '', 'color' => '', 'default' => ''],
                    ],
                ],
            ],
        ]),
    ])
        ->assertOk()
        ->assertJsonPath('values.types.'.Html::id(RadioButtons::class).'.options.0.label', $label);
});

it('omits the settings section when the field type has no settings', function () {
    app(FieldTypes::class)->register(SettingsFreeField::class);

    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => SettingsFreeField::class,
        'settings' => [],
    ])->assertOk()
        ->assertJsonPath('definition', null)
        ->assertJsonMissingPath('settingsHtml');
});

it('preserves unavailable field settings behind Missing Component behavior', function () {
    $expectedType = 'missing\\plugin\\Field';
    $field = FieldModel::factory()->create([
        'type' => $expectedType,
        'settings' => ['customSetting' => 'preserved value'],
    ]);
    Fields::invalidateCaches();
    $typeId = Html::id($expectedType);

    $this->get(action([FieldsController::class, 'edit'], ['fieldId' => $field->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/fields/Edit')
            ->where('field.type', $expectedType)
            ->where('settingsDefinition', null)
            ->where('settingsValues.types.'.$typeId.'.customSetting', 'preserved value')
            ->where('missingFieldPlaceholder', fn ($html) => is_string($html) && $html !== ''));
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

it('can save a new field with host-owned settings', function () {
    $typeId = Html::id(PlainText::class);

    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainTextWithNativeSettings',
        'types' => [
            $typeId => [
                'placeholder' => 'Type something…',
                'multiline' => true,
            ],
        ],
    ])->assertOk();

    tap(FieldModel::query()->latest('id')->firstOrFail(), function (FieldModel $field) {
        expect($field->handle)->toBe('plainTextWithNativeSettings');
        expect($field->settings['placeholder'])->toBe('Type something…');
        expect($field->settings['multiline'])->toBeTrue();
    });
});

it('saves native host settings without a compatibility fallback', function () {
    $typeId = Html::id(PlainText::class);

    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'My legacy plugin field',
        'handle' => 'legacyPluginField',
        'types' => [
            $typeId => [
                'placeholder' => 'Live host value',
            ],
        ],
        'typeSettings' => http_build_query([
            'types' => [
                $typeId => [
                    'placeholder' => 'Ignored compatibility value',
                ],
            ],
        ]),
    ])->assertOk();

    expect(FieldModel::query()->latest('id')->firstOrFail()->settings['placeholder'])
        ->toBe('Live host value');
});

it('returns field setting validation errors under the binding scope', function () {
    $typeId = Html::id(PlainText::class);

    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'Invalid plaintext field',
        'handle' => 'invalidPlainText',
        'types' => [
            $typeId => [
                'initialRows' => 0,
            ],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors("types.{$typeId}.initialRows");
});

class SettingsFreeField extends Field {}

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
