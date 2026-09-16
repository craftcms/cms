<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\SsoIdentity;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->provider = new ProviderDefinition(
        handle: 'test',
        driver: 'test',
        providerClass: null,
        name: 'Test',
        label: 'Test',
        clientId: null,
        clientSecret: null,
    );
});

it('selects identity presence with users without additional lookups', function () {
    $models = UserModel::factory()->count(3)->active()->create();
    app(OAuth::class)->linkIdentity($models[0]->asElement(), $this->provider, 'linked-identity');

    DB::enableQueryLog();
    $users = User::find()->id($models->modelKeys())->orderBy('id')->all();

    expect($users[0]->getHasSsoIdentity())->toBeTrue()
        ->and($users[1]->getHasSsoIdentity())->toBeFalse()
        ->and($users[2]->getHasSsoIdentity())->toBeFalse()
        ->and($users[0]->getHasSsoIdentity())->toBeTrue()
        ->and($users[1]->getHasSsoIdentity())->toBeFalse();

    $queries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], Table::SSO_IDENTITIES));
    DB::disableQueryLog();

    expect($queries)->toHaveCount(1);
});

it('reuses selected identity presence on clones and queries detached and scalar users', function () {
    $model = UserModel::factory()->active()->create();
    app(OAuth::class)->linkIdentity($model->asElement(), $this->provider, 'linked-identity');
    $user = User::find()->id($model->id)->one();
    $clone = clone $user;
    $detached = $model->asElement();

    DB::enableQueryLog();

    expect($user->getHasSsoIdentity())->toBeTrue()
        ->and($clone->getHasSsoIdentity())->toBeTrue()
        ->and($detached->getHasSsoIdentity())->toBeTrue()
        ->and(app(OAuth::class)->hasIdentity($user->id))->toBeTrue();

    $queries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], Table::SSO_IDENTITIES));
    DB::disableQueryLog();

    expect($queries)->toHaveCount(2);
});

it('looks up identities for users created without a query result', function () {
    $user = UserModel::factory()->active()->create()->asElement();
    app(OAuth::class)->linkIdentity($user, $this->provider, 'linked-identity');

    expect($user->elementQueryResult)->toBeNull()
        ->and($user->getHasSsoIdentity())->toBeTrue();
});

it('does not query identity presence for unsaved users', function () {
    DB::enableQueryLog();

    expect(UserModel::factory()->make()->asElement()->getHasSsoIdentity())->toBeFalse();

    $queries = array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], Table::SSO_IDENTITIES));
    DB::disableQueryLog();

    expect($queries)->toBe([]);
});

it('looks up identity presence when it was omitted from the user query', function () {
    $model = UserModel::factory()->active()->create();
    app(OAuth::class)->linkIdentity($model->asElement(), $this->provider, 'linked-identity');
    $user = User::find()->select('id')->id($model->id)->one();

    expect($user->getHasSsoIdentity())->toBeTrue();
});

it('refreshes a selected identity snapshot when the user is queried again', function () {
    $model = UserModel::factory()->active()->create();
    $user = User::find()->id($model->id)->one();
    $clone = clone $user;
    app(OAuth::class)->linkIdentity($model->asElement(), $this->provider, 'linked-identity');

    expect($user->getHasSsoIdentity())->toBeFalse()
        ->and($clone->getHasSsoIdentity())->toBeFalse()
        ->and(User::find()->id($model->id)->one()->getHasSsoIdentity())->toBeTrue()
        ->and(app(OAuth::class)->hasIdentity($model->id))->toBeTrue();
});

it('does not accept identity presence from request attributes', function () {
    $model = UserModel::factory()->active()->create();
    $user = User::find()->id($model->id)->one();

    $user->setAttributesFromRequest(['hasSsoIdentity' => true]);

    expect($user->getHasSsoIdentity())->toBeFalse();
});

it('refreshes identity presence in a new request or job scope', function () {
    $user = UserModel::factory()->active()->createElement();
    $oauth = app(OAuth::class);

    expect($user->getHasSsoIdentity())->toBeFalse();

    DB::table(Table::SSO_IDENTITIES)->insert([
        'provider' => 'test',
        'identityId' => 'identity-linked-in-another-process',
        'userId' => $user->id,
        'dateCreated' => now('UTC'),
        'dateUpdated' => now('UTC'),
    ]);
    app()->forgetScopedInstances();

    expect(app(OAuth::class))->not()->toBe($oauth)
        ->and($user->getHasSsoIdentity())->toBeTrue();
});

it('updates identity presence and suspension permissions after linking and unlinking', function () {
    $operator = UserModel::factory()->create(['admin' => true]);
    $model = UserModel::factory()->active()->create();
    $user = User::find()->id($model->id)->one();
    $oauth = app(OAuth::class);

    expect($user->getHasSsoIdentity())->toBeFalse()
        ->and(Gate::forUser($operator)->allows('suspend', $user))->toBeTrue();

    $oauth->linkIdentity($user, $this->provider, 'linked-identity');

    expect($user->getHasSsoIdentity())->toBeTrue()
        ->and(Gate::forUser($operator)->allows('suspend', $user))->toBeFalse();

    $oauth->unlinkIdentity($user, $this->provider);

    expect($user->getHasSsoIdentity())->toBeFalse()
        ->and(Gate::forUser($operator)->allows('suspend', $user))->toBeTrue();
});

it('refreshes both users when an identity moves to a different user', function () {
    $previousUser = UserModel::factory()->active()->createElement();
    $nextUser = UserModel::factory()->active()->createElement();
    $oauth = app(OAuth::class);
    $oauth->linkIdentity($previousUser, $this->provider, 'shared-identity');

    expect($previousUser->getHasSsoIdentity())->toBeTrue()
        ->and($nextUser->getHasSsoIdentity())->toBeFalse();

    $oauth->linkIdentity($nextUser, $this->provider, 'shared-identity');

    expect($previousUser->getHasSsoIdentity())->toBeFalse()
        ->and($nextUser->getHasSsoIdentity())->toBeTrue();
});

it('refreshes identity presence when identities are saved or deleted through the model', function () {
    $user = UserModel::factory()->active()->createElement();
    $nextUser = UserModel::factory()->active()->createElement();
    $clone = clone $user;

    expect($user->getHasSsoIdentity())->toBeFalse()
        ->and($nextUser->getHasSsoIdentity())->toBeFalse();

    $identity = SsoIdentity::query()->firstOrNew([
        'provider' => 'test',
        'identityId' => 'directly-linked-identity',
    ]);
    $identity->userId = $user->id;
    $identity->save();

    expect($clone->getHasSsoIdentity())->toBeTrue()
        ->and(app(OAuth::class)->hasIdentity($user->id))->toBeTrue();

    $identity = SsoIdentity::query()->where('userId', $user->id)->firstOrFail();
    $identity->setKeyName('identityId');
    $identity->userId = $nextUser->id;
    $identity->save();

    expect($user->getHasSsoIdentity())->toBeFalse()
        ->and($clone->getHasSsoIdentity())->toBeFalse()
        ->and($nextUser->getHasSsoIdentity())->toBeTrue();

    $identity->delete();

    expect($nextUser->getHasSsoIdentity())->toBeFalse();
});

it('keeps identity presence when another provider is still linked', function () {
    $user = UserModel::factory()->active()->createElement();
    $otherProvider = new ProviderDefinition(
        handle: 'other',
        driver: 'test',
        providerClass: null,
        name: 'Other',
        label: 'Other',
        clientId: null,
        clientSecret: null,
    );
    $oauth = app(OAuth::class);
    $oauth->linkIdentity($user, $this->provider, 'test-identity');
    $oauth->linkIdentity($user, $otherProvider, 'other-identity');

    expect($user->getHasSsoIdentity())->toBeTrue();

    $oauth->unlinkIdentity($user, $this->provider);

    expect($user->getHasSsoIdentity())->toBeTrue();

    $oauth->unlinkIdentity($user, $otherProvider);

    expect($user->getHasSsoIdentity())->toBeFalse();
});

it('restores identity presence after an outer transaction rolls back', function (bool $linked) {
    $user = UserModel::factory()->active()->createElement();
    $oauth = app(OAuth::class);
    if ($linked) {
        $oauth->linkIdentity($user, $this->provider, 'linked-identity');
    }

    expect($user->getHasSsoIdentity())->toBe($linked);

    DB::beginTransaction();
    try {
        if ($linked) {
            $oauth->unlinkIdentity($user, $this->provider);
        } else {
            $oauth->linkIdentity($user, $this->provider, 'linked-identity');
        }

        expect($user->getHasSsoIdentity())->toBe(! $linked);
    } finally {
        DB::rollBack();
    }

    expect($user->getHasSsoIdentity())->toBe($linked);
})->with(['link rollback' => false, 'unlink rollback' => true]);
