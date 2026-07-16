<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\LabelField;
use CraftCms\Cms\Http\Controllers\Settings\AddressSettingsController;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigPaths;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires authentication', function () {
    Auth::logout();

    get(action([AddressSettingsController::class, 'index']))->assertRedirect();
    post(action([AddressSettingsController::class, 'store']))->assertRedirect();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([AddressSettingsController::class, 'index']))->assertForbidden();
    post(action([AddressSettingsController::class, 'store']))->assertForbidden();
});

it('renders the inertia address fields screen', function () {
    get(action([AddressSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/addresses/Fields')
            ->where('title', 'Address Fields')
            ->has('fieldLayoutDesigner.html'))
        ->assertOk();
});

it('can save the address field layout', function () {
    $layoutUid = Str::uuid()->toString();

    post(action([AddressSettingsController::class, 'store']), [
        'fieldLayout' => json_encode([
            'uid' => $layoutUid,
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => LabelField::class,
                        ],
                    ],
                ],
            ],
        ]),
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get(sprintf('%s.%s', ProjectConfigPaths::PATH_ADDRESS_FIELD_LAYOUTS, $layoutUid)))
        ->not->toBeNull();
});
