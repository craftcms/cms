<?php

use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\TemplateHooks;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\renderString;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(cp_url('myaccount/preferences'))->assertRedirect();
    patchJson(cp_url('myaccount/preferences'))->assertUnauthorized();
});

test('index shows preferences page', function () {
    $response = get(cp_url('myaccount/preferences'));

    $response
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->where('ui.values.preferredLanguage', 'en-US')
            ->where('ui.values.displaySettings', [])
            ->where('ui.values.developmentSettings', [])
            ->where('readOnly', false)
            ->where('submit.method', 'patch')
            ->where('ui.nodes.0.props.label', t('General'))
            ->where('ui.nodes.0.children', fn (Collection $children): bool => $children->doesntContain('component', 'craft:template-content'))
            ->where('ui.nodes.2.props.label', t('Development'))
            ->has('subnav')
            ->where('metadataHtml', fn (?string $html): bool => filled($html)));
});

test('index leaves out the development tab for non-admins', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->create();

    actingAs($user->asElement());

    get(cp_url('myaccount/preferences'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->has('ui.nodes', 2)
            ->missing('ui.values.developmentSettings'));
});

test('index includes preferences hook output and assets', function () {
    app(TemplateHooks::class)->register('cp.users.edit.prefs', function (array &$context, bool &$handled): string {
        expect($context['user'])->toBeInstanceOf(User::class)
            ->and($context['isNewUser'])->toBeFalse();

        HtmlStack::cssFile('/prefs-hook.css');
        HtmlStack::js('window.prefsHookReady = true');

        return '<div data-hook="prefs">Preferences hook</div>';
    });

    get(cp_url('myaccount/preferences'))
        ->assertOk()
        ->assertSee('/prefs-hook.css', false)
        ->assertSee('window.prefsHookReady = true', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->where('ui.nodes.0.children', fn (Collection $children): bool => $children->contains(
                fn (array $node): bool => $node['component'] === 'craft:template-content'
                    && $node['props']['html'] === '<div data-hook="prefs">Preferences hook</div>',
            )));
});

test('update saves language', function () {
    /** @var User $user */
    $user = currentUser();

    expect($user->getPreference('language'))->toBe('en-US');

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'de',
    ])->assertOk();

    expect($user->getPreference('language'))->toBe('de');
});

test('update saves multiple preferences at once', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'fr',
        'weekStartDay' => 1,
        'useShapes' => true,
        'underlineLinks' => true,
    ])->assertOk();

    expect($user->getPreference('language'))->toBe('fr');
    expect($user->getPreference('weekStartDay'))->toBe(1);
    expect($user->getPreference('useShapes'))->toBeTrue();
    expect($user->getPreference('underlineLinks'))->toBeTrue();
});

test('update handles blank values', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLocale' => '__blank__',
        'timeZone' => '__blank__',
    ])->assertOk();

    expect($user->getPreference('locale'))->toBeNull();
    expect($user->getPreference('timeZone'))->toBeNull();
});

test('update saves notification preferences', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'notificationDuration' => 5000,
        'notificationPosition' => 'start-end',
    ])->assertOk();

    expect($user->getPreference('notificationDuration'))->toBe(5000);
    expect($user->getPreference('notificationPosition'))->toBe('start-end');
});

test('update saves slideout position preference', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'slideoutPosition' => 'start',
    ])->assertOk();

    expect($user->getPreference('slideoutPosition'))->toBe('start');
});

test('update saves admin-only preferences for admin users', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'showFieldHandles' => true,
        'showExceptionView' => true,
        'profileTemplates' => true,
    ])->assertOk();

    expect($user->getPreference('showFieldHandles'))->toBeTrue();
    expect($user->getPreference('showExceptionView'))->toBeTrue();
    expect($user->getPreference('profileTemplates'))->toBeTrue();
});

test('update rejects admin-only preferences for non-admin users', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->create();

    actingAs($user->asElement());

    patchJson(cp_url('myaccount/preferences'), [
        'showFieldHandles' => true,
    ])->assertForbidden();
});

test('update preserves existing preferences when not provided', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'de',
        'weekStartDay' => 0,
    ])->assertOk();

    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'es',
    ])->assertOk();

    expect($user->getPreference('language'))->toBe('es');
    expect($user->getPreference('weekStartDay'))->toBe(0);
});

test('update sets the booleans a checkbox list covers', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'displaySettings' => ['underlineLinks'],
        'generalSettings' => null,
        'developmentSettings' => ['showFieldHandles', 'profileTemplates'],
    ])->assertOk();

    expect($user->getPreference('useShapes'))->toBeFalse()
        ->and($user->getPreference('underlineLinks'))->toBeTrue()
        ->and($user->getPreference('disableAutofocus'))->toBeFalse()
        ->and($user->getPreference('showFieldHandles'))->toBeTrue()
        ->and($user->getPreference('profileTemplates'))->toBeTrue()
        ->and($user->getPreference('showExceptionView'))->toBeFalse();
});

test('update rejects the development settings list for non-admin users', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->create();

    actingAs($user->asElement());

    patchJson(cp_url('myaccount/preferences'), [
        'developmentSettings' => [],
    ])->assertForbidden();
});

test('update handles boolean preferences correctly', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), [
        'useShapes' => false,
        'underlineLinks' => false,
        'disableAutofocus' => true,
    ])->assertOk();

    expect($user->getPreference('useShapes'))->toBeFalse();
    expect($user->getPreference('underlineLinks'))->toBeFalse();
    expect($user->getPreference('disableAutofocus'))->toBeTrue();
});

test('update validates preference values', function () {
    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'not-a-locale',
        'weekStartDay' => 8,
        'timeZone' => 'not-a-timezone',
        'notificationDuration' => 1234,
        'notificationPosition' => 'top-right',
        'slideoutPosition' => 'left',
        'theme' => 'sepia',
    ])->assertJsonValidationErrors([
        'preferredLanguage',
        'weekStartDay',
        'timeZone',
        'notificationDuration',
        'notificationPosition',
        'slideoutPosition',
        'theme',
    ]);
});

test('update returns success message', function () {
    patchJson(cp_url('myaccount/preferences'), [
        'preferredLanguage' => 'en-US',
    ])
        ->assertOk()
        ->assertJson([
            'message' => t('Preferences saved.'),
        ]);
});

test('the underline links and use shapes preferences are shared with every screen', function () {
    get(cp_url('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('craft.currentUser.underlineLinks', false)
            ->where('craft.currentUser.useShapes', false));

    patchJson(cp_url('myaccount/preferences'), ['underlineLinks' => true])->assertOk();

    get(cp_url('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('craft.currentUser.underlineLinks', true)
            ->where('craft.currentUser.useShapes', false));

    patchJson(cp_url('myaccount/preferences'), ['useShapes' => true])->assertOk();

    get(cp_url('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('craft.currentUser.useShapes', true));
});

test('update saves the theme', function () {
    /** @var User $user */
    $user = currentUser();

    patchJson(cp_url('myaccount/preferences'), ['theme' => 'dark'])->assertOk();

    expect($user->getPreference('theme'))->toBe('dark');

    get(cp_url('myaccount/preferences'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('ui.values.theme', 'dark'));
});

test('the theme is applied to the control panel root, following the system by default', function () {
    get(cp_url('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('craft.theme', 'system'))
        ->assertDontSee('data-theme="dark">', false)
        ->assertSee("matchMedia('(prefers-color-scheme: dark)')", false);

    patchJson(cp_url('myaccount/preferences'), ['theme' => 'dark'])->assertOk();

    get(cp_url('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('craft.theme', 'dark'))
        ->assertSee('<html', false)
        ->assertSee('data-theme="dark">', false);

    patchJson(cp_url('myaccount/preferences'), ['theme' => 'light'])->assertOk();

    get(cp_url('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('craft.theme', 'light'))
        ->assertDontSee('data-theme="dark"', false)
        ->assertDontSee('prefers-color-scheme', false);
});

test('logged-out pages follow the system theme, even with a theme cookie from an earlier version', function () {
    auth()->logout();

    $this->withCookie(config('session.cookie').'_theme', 'dark')
        ->get(cp_url('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('craft.theme', 'system'))
        ->assertSee("matchMedia('(prefers-color-scheme: dark)')", false);
});

test('the theme is applied to legacy control panel pages', function (?string $theme, bool $darkAttribute, bool $systemScript) {
    if ($theme !== null) {
        patchJson(cp_url('myaccount/preferences'), ['theme' => $theme])->assertOk();
    }

    $html = renderString("{% extends '_layouts/base' %}", ['title' => 'Test'], TemplateMode::Cp);

    expect(str_contains($html, 'data-theme="dark">'))->toBe($darkAttribute)
        ->and(str_contains($html, 'prefers-color-scheme'))->toBe($systemScript);
})->with([
    'unset' => [null, false, true],
    'light' => ['light', false, false],
    'dark' => ['dark', true, false],
    'system' => ['system', false, true],
]);
