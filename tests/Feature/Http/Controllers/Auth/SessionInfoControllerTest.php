<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Auth\SessionInfoController;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Tests\TestClasses\OAuth\FakeOAuthProvider;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\TemplateHooks;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

test('session info returns csrf token without extending the session', function () {
    $response = getJson(action([SessionInfoController::class, 'show'], [
        'dontExtendSession' => 1,
    ]));

    $response
        ->assertOk()
        ->assertHeaderMissing('Set-Cookie')
        ->assertJson([
            'isGuest' => true,
            'csrfTokenName' => '_token',
        ]);

    expect($response->json('csrfTokenValue'))->toBeString();
});

test('elevated session timeout does not extend the session', function () {
    $response = getJson(action([SessionInfoController::class, 'confirmTimeout']));

    $response
        ->assertOk()
        ->assertHeaderMissing('Set-Cookie')
        ->assertJson([
            'timeout' => 0,
        ]);
});

test('password confirmation requires authentication', function () {
    postJson(action([SessionInfoController::class, 'confirmPassword']))
        ->assertUnauthorized();
});

test('password confirmation requires control panel access', function () {
    $user = UserModel::factory()->active()->create();
    actingAs($user);

    postJson(action([SessionInfoController::class, 'confirmPassword']))
        ->assertForbidden();
});

test('password confirmation reports a sufficient confirmation', function () {
    Date::setTestNow(now());
    actingAs(User::findOne());
    Session::passwordConfirmed();
    travel(10)->seconds();

    postJson(action([SessionInfoController::class, 'confirmPassword']), [
        'minimumRemainingSeconds' => 5,
    ])->assertOk()->assertJson([
        'confirmed' => true,
        'timeout' => config('auth.password_timeout') - 10,
    ]);
});

test('password confirmation invalidates an insufficient confirmation', function () {
    Date::setTestNow(now());
    actingAs(User::findOne());
    Session::passwordConfirmed();

    postJson(action([SessionInfoController::class, 'confirmPassword']), [
        'minimumRemainingSeconds' => config('auth.password_timeout') + 1,
    ])->assertOk()->assertJson([
        'confirmed' => false,
        'timeout' => 0,
        'loginName' => 'support@craftcms.com',
        'alternativeLoginMethods' => null,
    ]);

    expect(Session::has('auth.password_confirmed_at'))->toBeFalse();
});

test('password confirmation can be forced', function () {
    actingAs(User::findOne());
    Session::passwordConfirmed();

    postJson(action([SessionInfoController::class, 'confirmPassword']), [
        'force' => true,
    ])->assertOk()->assertJsonPath('confirmed', false);

    expect(Session::has('auth.password_confirmed_at'))->toBeFalse();
});

test('password confirmation remains confirmed when disabled', function () {
    config()->set('auth.password_timeout', -1);
    actingAs(User::findOne());

    postJson(action([SessionInfoController::class, 'confirmPassword']), [
        'force' => true,
    ])->assertOk()->assertJson([
        'confirmed' => true,
        'timeout' => false,
    ]);
});

test('password confirmation uses the impersonator login name', function () {
    $impersonator = UserModel::factory()->admin()->createElement([
        'email' => 'impersonator@example.com',
    ]);
    $impersonated = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement([
            'email' => 'impersonated@example.com',
        ]);
    actingAs($impersonated);
    app(Impersonation::class)->setImpersonatorId($impersonator->id);

    postJson(action([SessionInfoController::class, 'confirmPassword']))
        ->assertOk()
        ->assertJsonPath('loginName', 'impersonator@example.com');
});

test('password confirmation captures alternative login methods and assets', function () {
    actingAs(User::findOne());
    app(TemplateHooks::class)->register('cp.login.alternative-login-methods', function (array &$context): string {
        expect($context['forElevatedSession'])->toBeTrue()
            ->and($context['staticEmail'])->toBe('support@craftcms.com')
            ->and($context['username'])->toBe('support@craftcms.com');

        HtmlStack::cssFile('/alternative-login.css');
        HtmlStack::js('window.alternativeLoginReady = true');

        return '<button>Alternative login</button>';
    });

    postJson(action([SessionInfoController::class, 'confirmPassword']))
        ->assertOk()
        ->assertJsonPath('alternativeLoginMethods.html', '<button>Alternative login</button>')
        ->assertJsonPath('alternativeLoginMethods.headHtml', fn (string $html): bool => str_contains($html, '/alternative-login.css'))
        ->assertJsonPath('alternativeLoginMethods.bodyHtml', fn (string $html): bool => str_contains($html, 'window.alternativeLoginReady = true'));
});

test('password confirmation includes configured OAuth buttons', function () {
    Edition::set(Edition::Pro);
    actingAs(User::findOne());
    app(GeneralConfig::class)->oauthProviders([
        'test' => [
            'driver' => FakeOAuthProvider::class,
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'label' => 'Continue with Test OAuth',
        ],
    ]);

    postJson(action([SessionInfoController::class, 'confirmPassword']))
        ->assertOk()
        ->assertJsonPath('alternativeLoginMethods.html', fn (string $html): bool => str_contains($html, 'Continue with Test OAuth') &&
            str_contains($html, cp_url('oauth/test/redirect')));
});

test('password confirmation validates its options', function () {
    actingAs(User::findOne());

    postJson(action([SessionInfoController::class, 'confirmPassword']), [
        'minimumRemainingSeconds' => -1,
        'force' => 'sometimes',
    ])->assertJsonValidationErrors(['minimumRemainingSeconds', 'force']);
});
