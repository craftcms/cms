<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserSettingsController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());

    config()->set('filesystems.disks.user-photo-public', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/user-settings-test/public'),
        'url' => 'https://example.test/uploads',
    ]);
    config()->set('filesystems.disks.user-photo-private', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/user-settings-test/private'),
    ]);
});

it('requires authentication', function () {
    Auth::logout();

    get(action([UserSettingsController::class, 'index']))->assertRedirect();
    post(action([UserSettingsController::class, 'renderForm']))->assertRedirect();
    post(action([UserSettingsController::class, 'store']))->assertRedirect();
});

it('requires admin changes to save settings', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([UserSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/Settings')
            ->where('readOnly', true));

    post(action([UserSettingsController::class, 'renderForm']))->assertForbidden();
    post(action([UserSettingsController::class, 'store']))->assertForbidden();
});

it('renders the inertia user settings screen', function () {
    get(action([UserSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/Settings')
            ->where('title', 'User Settings')
            ->where('form.values.requireEmailVerification', true)
            ->where('form.values.allowPublicRegistration', false)
            ->where('form.refreshable', true)
            ->where('submit.method', 'post')
            ->where('submit.url', action([UserSettingsController::class, 'store']))
            ->where('refreshUrl', action([UserSettingsController::class, 'renderForm']))
            ->where('form.nodes', function ($nodes) {
                $paths = collect($nodes)->pluck('control.path');

                return $paths->contains(['require2fa'])
                    && $paths->contains(['allowPublicRegistration']);
            })
            ->has('subnav', 3))
        ->assertOk();
});

it('only shows settings supported by the current edition', function () {
    Edition::set(Edition::Solo);

    get(action([UserSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('refreshUrl', null)
            ->where('form.nodes', function ($nodes) {
                $paths = collect($nodes)->pluck('control.path');

                return $paths->doesntContain(['require2fa'])
                    && $paths->doesntContain(['allowPublicRegistration']);
            }));
});

it('uses the control panel route for saving settings', function () {
    post(action([UserSettingsController::class, 'store']))
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    post(sprintf(
        '/%s/%s/user-settings/save-user-settings',
        Cms::config()->cpTrigger,
        Cms::config()->actionTrigger,
    ))->assertMethodNotAllowed();
});

it('exposes all user photo volumes', function () {
    $publicVolume = Volume::factory()->create([
        'name' => 'Public Photos',
        'handle' => 'publicPhotos',
        'fs' => 'disk:user-photo-public',
    ]);
    $privateVolume = Volume::factory()->create([
        'name' => 'Private Photos',
        'handle' => 'privatePhotos',
        'fs' => 'disk:user-photo-private',
    ]);
    app(Volumes::class)->reset();

    get(action([UserSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', function ($nodes) use ($publicVolume, $privateVolume) {
                $control = collect($nodes)
                    ->pluck('control')
                    ->firstWhere('path', ['photoVolumeUid']);
                $values = collect($control['props']['options'])->pluck('value');

                return $values->contains((string) $publicVolume->uid)
                    && $values->contains((string) $privateVolume->uid)
                    && $values->contains('__createVolume__');
            }));
});

it('refreshes public registration fields without losing their current values', function () {
    $values = [
        'photoVolumeUid' => '',
        'photoSubpath' => '',
        'require2fa' => false,
        'requireEmailVerification' => true,
        'allowPublicRegistration' => true,
        'validateOnPublicRegistration' => true,
        'deactivateByDefault' => true,
        'defaultGroup' => '',
    ];

    $shown = post(action([UserSettingsController::class, 'renderForm']), [
        'values' => $values,
        'scope' => [],
    ])->assertOk()
        ->assertJsonPath('form.values.allowPublicRegistration', true)
        ->assertJsonPath('form.values.validateOnPublicRegistration', true);

    $hidden = post(action([UserSettingsController::class, 'renderForm']), [
        'values' => [...$values, 'allowPublicRegistration' => false],
        'scope' => [],
    ])->assertOk()
        ->assertJsonPath('form.values.allowPublicRegistration', false)
        ->assertJsonPath('form.values.validateOnPublicRegistration', true);

    $shownNode = collect($shown->json('form.nodes'))->firstWhere('control.path', ['validateOnPublicRegistration']);
    $hiddenNode = collect($hidden->json('form.nodes'))->firstWhere('control.path', ['validateOnPublicRegistration']);

    expect($shownNode['component'])->toBe('craft:field')
        ->and($hiddenNode['component'])->toBe('craft:hidden-field');
});

it('rejects unknown user photo volume uids', function () {
    post(action([UserSettingsController::class, 'store']), [
        'photoVolumeUid' => Str::uuid()->toString(),
    ])->assertSessionHasErrors('photoVolumeUid');
});

it('can save a user photo volume uid', function () {
    $privateVolume = Volume::factory()->create([
        'name' => 'Private Photos',
        'handle' => 'privatePhotos',
        'fs' => 'disk:user-photo-private',
    ]);
    app(Volumes::class)->reset();

    post(action([UserSettingsController::class, 'store']), [
        'photoVolumeUid' => $privateVolume->uid,
        'photoSubpath' => '{username}',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('users.photoVolumeUid'))->toBe($privateVolume->uid)
        ->and(ProjectConfig::get('users.photoSubpath'))->toBe('{username}');
});

test('require2fa only gets saved when above team edition', function () {
    Edition::set(Edition::Solo);

    post(action([UserSettingsController::class, 'store']), [
        'require2fa' => 'all',
    ])->assertRedirectBack();

    expect(ProjectConfig::get('users.require2fa'))->toBeFalsy();

    Edition::set(Edition::Team);

    post(action([UserSettingsController::class, 'store']), [
        'require2fa' => 'all',
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('users.require2fa'))->toBe('all');
});

it('normalizes selected require2fa groups', function () {
    Edition::set(Edition::Pro);
    $group = UserGroup::factory()->create();

    post(action([UserSettingsController::class, 'store']), [
        'require2fa' => ['admins', $group->uid],
    ])->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(ProjectConfig::get('users.require2fa'))->toBe(['admins', $group->uid]);
});

it('rejects invalid require2fa values', function () {
    Edition::set(Edition::Pro);

    post(action([UserSettingsController::class, 'store']), [
        'require2fa' => ['nope'],
    ])->assertSessionHasErrors('require2fa');
});

test('user settings only get saved when above pro edition', function (string $property, mixed $value) {
    if ($property === 'defaultGroup') {
        $value = UserGroup::factory()->create()->uid;
    }

    Edition::set(Edition::Team);

    $initialValue = ProjectConfig::get("users.$property");

    post(action([UserSettingsController::class, 'store']), [
        $property => $value,
    ])->assertRedirectBack();

    expect(ProjectConfig::get("users.$property"))->toBe($initialValue);

    Edition::set(Edition::Pro);

    post(action([UserSettingsController::class, 'store']), [
        $property => $value,
    ])->assertRedirectBack();

    expect(ProjectConfig::get("users.$property"))->toBe($value);
})->with([
    'require email verification' => ['requireEmailVerification', false],
    'validate public registration' => ['validateOnPublicRegistration', true],
    'allow public registration' => ['allowPublicRegistration', true],
    'deactivate by default' => ['deactivateByDefault', true],
    'default group' => ['defaultGroup', null],
]);
