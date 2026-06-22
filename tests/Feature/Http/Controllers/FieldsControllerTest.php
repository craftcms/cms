<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Http\Controllers\FieldsController;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
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
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Fields'));
});

it('can create a new field', function () {
    $this->get(action([FieldsController::class, 'create']))
        ->assertSee('Create a new field')
        ->assertSee('<span class="icon">', false);
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
    $label = Str::random();

    $this->postJson(action([FieldsController::class, 'renderSettings']), [
        'type' => RadioButtons::class,
        'oldType' => MultiSelect::class,
        'oldNamespace' => 'namespace',
        'settings' => http_build_query([
            'namespace' => [
                'options' => [
                    ['label' => $label, 'value' => 'value', 'icon' => '', 'color' => '', 'default' => ''],
                ],
            ],
        ]),
    ])
        ->assertOk()
        ->assertSee($label);
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
