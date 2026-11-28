<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use function CraftCms\Cms\cp_url;

/**
 * @return array<string, mixed> a session signed in as the user
 */
function signedInSession(User $user): array
{
    $guard = Cms::config()->security->getAuthGuard();

    return [
        Auth::guard($guard)->getName() => $user->id,
        "password_hash_$guard" => UserModel::findOrFail($user->id)->getAuthPassword(),
    ];
}

it('signs out users whose account stopped being active', function (Closure $change): void {
    $user = UserModel::factory()->createElement(['admin' => true]);
    $session = signedInSession($user);

    $change($user);

    AuthenticateSession::redirectUsing(fn () => null);

    try {
        $this->withSession($session)->getJson(cp_url('dashboard'))->assertUnauthorized();
    } finally {
        AuthenticateSession::redirectUsing(fn () => route('login'));
    }

    expect(Auth::guard(Cms::config()->security->getAuthGuard())->guest())->toBeTrue();
})->with([
    'deactivated' => [fn (User $user) => Users::deactivateUser($user)],
    'suspended' => [fn (User $user) => Users::suspendUser($user)],
    'pending' => [fn (User $user) => UserModel::whereKey($user->id)->update(['active' => false, 'pending' => true])],
]);

it('lets users who stopped being active keep browsing the site as a guest', function (): void {
    Route::get('_test/craft-session-status', fn () => new JsonResponse([
        'userId' => Auth::guard(Cms::config()->security->getAuthGuard())->id(),
    ]))->middleware(['craft', 'craft.web']);
    $user = UserModel::factory()->createElement(['admin' => true]);
    $session = signedInSession($user);

    Users::suspendUser($user);

    $this->withSession($session)->getJson('_test/craft-session-status')
        ->assertOk()
        ->assertJsonPath('userId', null);
});

it('keeps locked and impersonated users signed in', function (string $case): void {
    $user = UserModel::factory()->createElement(['admin' => true]);
    $session = signedInSession($user);

    if ($case === 'locked') {
        UserModel::whereKey($user->id)->update(['locked' => true, 'lockoutDate' => now('UTC')]);
    } else {
        Users::suspendUser($user);
        $session['__impersonator_id'] = UserModel::factory()->createElement(['admin' => true])->id;
    }

    $this->withSession($session)->get(cp_url('dashboard'))->assertOk();
})->with(['locked', 'impersonated']);
