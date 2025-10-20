<?php

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());
});

it('needs authentication and admin changes for the routes', function (string $method, array $route, bool $requireAdminChanges) {
    auth()->logout();

    $this->$method(action($route))->assertUnauthorized();

    User::first()->update(['admin' => false]);
    actingAs(User::first());

    $this->$method(action($route))->assertForbidden();

    User::first()->update(['admin' => true]);
    actingAs(User::first());

    if ($requireAdminChanges) {
        app(GeneralConfig::class)->allowAdminChanges(false);

        $this->$method(action($route))->assertForbidden();
    }
})->with([
    ['getJson', [FieldsController::class, 'index'], false],
    ['getJson', [FieldsController::class, 'edit'], false],
    ['postJson', [FieldsController::class, 'renderSettings'], true],
    ['postJson', [FieldsController::class, 'store'], true],
    ['postJson', [FieldsController::class, 'destroy'], true],
    ['postJson', [FieldsController::class, 'renderLayoutComponentSettings'], true],
    ['postJson', [FieldsController::class, 'applyLayoutTabSettings'], true],
    ['postJson', [FieldsController::class, 'applyLayoutElementSettings'], true],
    ['postJson', [FieldsController::class, 'renderCardPreview'], true],
    ['getJson', [FieldsController::class, 'tableData'], false],
]);

it('can render the index', function () {
    $this->get(action([FieldsController::class, 'index']))
        ->assertOk()
        ->assertSee('No fields exist yet.');
});

it('can create a new field', function () {
    $this->get(action([FieldsController::class, 'edit']))
        ->assertSee('Create a new field');
});

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
        ->assertOk()
        ->assertSee('My plaintext field')
        ->assertSee('plainText');
});

it('can render the settings of a field', function () {
    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => PlainText::class,
    ])->assertOk();
});

it('preserves values between rendering settings', function () {
    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => RadioButtons::class,
        'oldType' => MultiSelect::class,
        'oldNamespace' => 'namespace',
        'settings' => http_build_query([
            'namespace' => [
                'options' => [
                    ['label' => 'Foo', 'value' => 'foo', 'icon' => '', 'color' => '', 'default' => ''],
                ],
            ],
        ]),
    ])
        ->assertOk()
        ->assertSee('name=\"options[0][label]\" value=\"Foo\"', false) // Label input
        ->assertSee('name=\"options[0][value]\" value=\"foo\"', false); // Value input
});

it('can save a new field', function () {
    $currentCount = FieldModel::count();

    $this->postJson(action([FieldsController::class, 'store']), [
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ])->assertOk();

    expect(FieldModel::count())->toBe($currentCount + 1);
    tap(FieldModel::latest('id')->first(), function (FieldModel $field) {
        expect($field->name)->toBe('My plaintext field');
        expect($field->handle)->toBe('plainText');
        expect($field->type)->toBe(PlainText::class);
    });
});

it('can delete a field', function () {
    Fields::saveField($field = Fields::createField([
        'type' => PlainText::class,
        'name' => 'My plaintext field',
        'handle' => 'plainText',
    ]));

    $currentCount = FieldModel::count();

    $this->postJson(action([FieldsController::class, 'destroy'], ['fieldId' => $field->id]))
        ->assertOk();

    expect(FieldModel::count())->toBe($currentCount - 1);
});
