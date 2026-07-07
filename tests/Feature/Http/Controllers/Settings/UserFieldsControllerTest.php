<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserFieldsController;
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

    get(action([UserFieldsController::class, 'index']))->assertRedirect();
    post(action([UserFieldsController::class, 'store']))->assertRedirect();
});

it('requires admin changes to save the field layout', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([UserFieldsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/Fields')
            ->where('readOnly', true));

    post(action([UserFieldsController::class, 'store']))->assertForbidden();
});

it('renders the inertia user profile fields screen', function () {
    get(action([UserFieldsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/Fields')
            ->where('title', 'User Settings')
            ->has('fieldLayoutDesigner.html')
        )
        ->assertOk();
});

it('can save the user field layout', function () {
    $layoutUid = Str::uuid()->toString();

    post(action([UserFieldsController::class, 'store']), [
        'fieldLayout' => json_encode([
            'uid' => $layoutUid,
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Profile',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => HorizontalRule::class,
                        ],
                    ],
                ],
            ],
        ]),
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get(sprintf('%s.%s', ProjectConfigPaths::PATH_USER_FIELD_LAYOUTS, $layoutUid)))
        ->not->toBeNull();
});

it('rejects reserved field handles', function () {
    post(action([UserFieldsController::class, 'store']), [
        'fieldLayout' => json_encode([
            'uid' => Str::uuid()->toString(),
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Profile',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => HorizontalRule::class,
                        ],
                    ],
                ],
            ],
        ]),
        'generatedFields' => [
            [
                'uid' => Str::uuid()->toString(),
                'name' => 'Username',
                'handle' => 'username',
                'template' => '',
            ],
        ],
    ])->assertSessionHas('error');
});
