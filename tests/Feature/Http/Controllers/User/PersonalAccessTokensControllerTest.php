<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use CraftCms\Cms\Auth\PersonalAccessTokens\ScopeGroup;
use CraftCms\Cms\Http\Controllers\Users\PersonalAccessTokensController;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    openssl_pkey_export($key, $privateKey);
    config()->set('passport.private_key', $privateKey);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);

    $this->user = User::query()->firstOrFail();
    actingAs($this->user);
    Session::passwordConfirmed();

    Route::middleware('auth:craft-mcp')->get('personal-access-token-test', fn () => auth('craft-mcp')->id());
});

function registerTestScopeGroup(?Closure $userScopes = null): void
{
    $tokens = app(PersonalAccessTokens::class);
    $tokens->registerScopeGroup('test-api', fn () => new ScopeGroup(
        handle: 'test-api',
        label: 'Test API',
        scopes: ['test-api:read' => 'Read test data', 'test-api:write' => 'Change test data'],
        userScopes: $userScopes,
    ));
    $tokens->registerPassportScopes();
}

function createTestToken(array $overrides = []): string
{
    return postJson(action([PersonalAccessTokensController::class, 'store']), [
        'name' => 'Build script',
        'group' => 'test-api',
        'scopes' => ['test-api:read'],
        'expiresIn' => 7,
        ...$overrides,
    ])->assertOk()->json('token');
}

it('hides the screen when no scope group is registered', function (): void {
    get(action([PersonalAccessTokensController::class, 'index']))->assertForbidden();
});

it('lists the scope groups and scopes the user may grant', function (): void {
    registerTestScopeGroup(fn (UserElement $user, array $scopes) => ['test-api:read']);

    get(action([PersonalAccessTokensController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Tokens')
            ->where('groups', [[
                'handle' => 'test-api',
                'label' => 'Test API',
                'scopes' => [['value' => 'test-api:read', 'label' => 'Read test data']],
            ]])
            ->where('grants', []));
});

it('requires an elevated session to create a token', function (): void {
    registerTestScopeGroup();
    Session::forget('auth.password_confirmed_at');

    postJson(action([PersonalAccessTokensController::class, 'store']), [])->assertStatus(423);
});

it('creates Passport personal access tokens that authenticate as the user', function (): void {
    registerTestScopeGroup();

    $token = createTestToken();
    createTestToken(['name' => 'Preview', 'expiresIn' => 365]);

    $this->withToken($token)->get('personal-access-token-test')->assertOk()->assertContent((string) $this->user->id);

    $stored = Passport::token()->newQuery()->where('name', 'Build script')->sole();
    expect($stored->scopes)->toBe(['test-api:read'])
        ->and($stored->expires_at->diffInSeconds(now()->addDays(7), true))->toBeLessThan(60)
        ->and(Passport::token()->newQuery()->where('name', 'Preview')->sole()->expires_at->diffInSeconds(now()->addDays(365), true))->toBeLessThan(60)
        ->and(Passport::personalAccessTokensExpireIn()->y)->toBe(1);

    $client = app(ClientRepository::class)->personalAccessClient($this->user->getProviderName());
    expect(Passport::token()->newQuery()->distinct()->pluck('client_id')->all())->toBe([$client->id])
        ->and($client->grant_types)->toBe(['personal_access']);
});

it('rejects scopes the user may not grant', function (array $scopes): void {
    registerTestScopeGroup(fn (UserElement $user, array $scopes) => ['test-api:read']);

    postJson(action([PersonalAccessTokensController::class, 'store']), [
        'name' => 'Build script',
        'group' => 'test-api',
        'scopes' => $scopes,
        'expiresIn' => 7,
    ])->assertUnprocessable()->assertJsonValidationErrors('scopes');

    expect(Passport::token()->newQuery()->count())->toBe(0);
})->with([
    'withheld scope' => [['test-api:write']],
    'unregistered scope' => [['mcp:admin']],
    'no scopes' => [[]],
]);

it('lists and revokes tokens and authorized apps for registered scope groups only', function (): void {
    registerTestScopeGroup();
    $token = createTestToken();
    createTestToken(['name' => 'Preview']);
    $app = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Test app', ['https://client.example/callback']);
    foreach ([['test-api:read', 'test-api:write'], ['mcp:read']] as $i => $scopes) {
        Passport::token()->newQuery()->forceCreate([
            'id' => "app-token-$i",
            'user_id' => $this->user->id,
            'client_id' => $app->id,
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => Query::prepareDateForDb(now()->addHour()),
        ]);
    }

    $grants = collect(app(PersonalAccessTokens::class)->grants($this->user->asElement()))->keyBy('name');
    expect($grants->keys()->sort()->values()->all())->toBe(['Build script', 'Preview', 'Test app'])
        ->and($grants['Build script']['personal'])->toBeTrue()
        ->and($grants['Test app']['personal'])->toBeFalse()
        ->and($grants['Test app']['scopes'])->toBe(['test-api:read', 'test-api:write']);

    postJson(action([PersonalAccessTokensController::class, 'destroy']), [
        'group' => 'test-api',
        'clientId' => $grants['Build script']['clientId'],
        'tokenId' => $grants['Build script']['tokenId'],
    ])->assertOk();
    postJson(action([PersonalAccessTokensController::class, 'destroy']), [
        'group' => 'test-api',
        'clientId' => (string) $app->id,
    ])->assertOk();

    $this->withToken($token)->getJson('personal-access-token-test')->assertUnauthorized();
    expect(collect(app(PersonalAccessTokens::class)->grants($this->user->asElement()))->pluck('name')->all())->toBe(['Preview'])
        ->and(Passport::token()->newQuery()->find('app-token-1')->revoked)->toBeFalse();
});

it('keeps the application’s scope descriptions when adding registered scopes to Passport', function (): void {
    Passport::tokensCan(['test-api:read' => 'Host description', 'host:scope' => 'Host scope']);

    registerTestScopeGroup();

    expect(Passport::scopes()->pluck('description', 'id')->all())->toMatchArray([
        'test-api:read' => 'Host description',
        'test-api:write' => 'Change test data',
        'host:scope' => 'Host scope',
    ]);
});
