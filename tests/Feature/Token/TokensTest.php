<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\RouteToken\Model\RouteToken;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Json;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->tokens = app(RouteTokens::class);
});

it('can create tokens', function () {
    $this->freezeSecond();

    $token = $this->tokens->createToken('do/stuff', 1, $expiryDate = now()->addDay());

    $tokenModel = RouteToken::where('token', $token)->firstOrFail();

    expect($tokenModel->route)->toBe('do/stuff');
    expect($tokenModel->usageLimit)->toBe(1);
    expect($tokenModel->usageCount)->toBe(0);
    expect($tokenModel->expiryDate->timestamp)->toBe($expiryDate->timestamp);
    expect(strlen((string) $token))->toBe(32);
});

it('can create a preview token', function () {
    $token = $this->tokens->createPreviewToken('do/stuff');

    $tokenModel = RouteToken::where('token', $token)->firstOrFail();

    expect($tokenModel->route)->toBe('do/stuff');
    expect(strlen((string) $token))->toBe(32);
});

it('creates tokens with config defaults', function () {
    Cms::config()->defaultTokenDuration = 10000;

    $this->freezeSecond();

    $token = $this->tokens->createToken('do/stuff');

    expect(strlen((string) $token))->toBe(32);

    $model = RouteToken::where('token', $token)->firstOrFail();

    expect($model->usageLimit)->toBeNull();
    expect($model->usageCount)->toBeNull();
    expect($model->expiryDate->timestamp)->toBe(now()->addSeconds(10000)->timestamp);
});

it('can get a token route', function () {
    $token = $this->tokens->createToken('do/stuff');

    $route = $this->tokens->getTokenRoute($token);

    expect($route)->not()->toBeFalse();
    expect($route[0])->toBe('do/stuff');
});

it('increments usage count when there is a usage limit', function () {
    $token = $this->tokens->createToken('do/stuff', 10);

    $model = RouteToken::where('token', $token)->firstOrFail();

    expect($model->usageLimit)->toBe(10);
    expect($model->usageCount)->toBe(0);

    $this->tokens->getTokenRoute($token);

    expect($model->fresh()->usageCount)->toBe(1);
});

it('deletes the token when the usage limit is reached', function () {
    $token = $this->tokens->createToken('do/stuff', 1);

    $model = RouteToken::where('token', $token)->firstOrFail();

    $this->tokens->getTokenRoute($token);

    expect($model->fresh())->toBeNull();
});

it('resolves a single-use token to the same route when it is looked up twice in a request', function () {
    $token = $this->tokens->createToken('do/stuff', 1);

    $first = $this->tokens->getTokenRoute($token);

    expect($first)->toBe(['do/stuff']);
    expect($this->tokens->getTokenRoute($token))->toBe($first);
    expect(RouteToken::where('token', $token)->exists())->toBeFalse();
});

it('counts a token use once per request', function () {
    $token = $this->tokens->createToken('do/stuff', 10);

    $this->tokens->getTokenRoute($token);
    $this->tokens->getTokenRoute($token);

    expect(RouteToken::where('token', $token)->firstOrFail()->usageCount)->toBe(1);
});

it('doesn’t grant a token use that another request claimed after it was read', function (int $usageLimit, array|false $expectedRoute) {
    $token = $this->tokens->createToken('do/stuff', $usageLimit);
    $id = RouteToken::where('token', $token)->value('id');

    // Another request uses the token right after this one reads it
    $competed = false;
    DB::listen(function (QueryExecuted $query) use (&$competed, $id) {
        if (! $competed && str_contains($query->sql, 'routetokens') && str_starts_with($query->sql, 'select')) {
            $competed = true;
            $competitor = DB::table(Table::ROUTETOKENS)->where('id', $id);
            $competitor->value('usageCount') + 1 < $competitor->value('usageLimit')
                ? $competitor->increment('usageCount')
                : $competitor->delete();
        }
    });

    expect($this->tokens->getTokenRoute($token))->toBe($expectedRoute)
        ->and(RouteToken::where('token', $token)->exists())->toBeFalse();
})->with([
    'its last use' => [1, false],
    'its second-last use' => [2, ['do/stuff']],
]);

it('decodes if the route is json', function () {
    $token = $this->tokens->createToken(Json::encode([
        'route' => 'do/stuff',
    ]));

    $route = $this->tokens->getTokenRoute($token);

    expect($route['route'])->toBe('do/stuff');
});

it('can increment usage count for a token', function () {
    $token = $this->tokens->createToken('do/stuff', 10);

    $model = RouteToken::where('token', $token)->firstOrFail();

    expect($model->usageCount)->toBe(0);

    $this->tokens->incrementTokenUsageCountById($model->id);

    expect($model->fresh()->usageCount)->toBe(1);
});

it('can delete tokens by id', function () {
    $token = $this->tokens->createToken('do/stuff', 10);

    $model = RouteToken::where('token', $token)->firstOrFail();

    $this->tokens->deleteTokenById($model->id);

    expect($model->fresh())->toBeNull();
});

it('can delete expired tokens', function () {
    $token = $this->tokens->createToken('do/stuff', 10);

    $model = RouteToken::where('token', $token)->firstOrFail();

    $model->update(['expiryDate' => now()->subSecond()]);

    $this->tokens->deleteExpiredTokens();

    expect($model->fresh())->toBeNull();
});

it('deletes expired tokens when getting token routes', function () {
    $token = $this->tokens->createToken('do/stuff', 10);
    $model = RouteToken::where('token', $token)->firstOrFail();
    $model->update(['expiryDate' => now()->subSecond()]);

    expect($this->tokens->getTokenRoute($token))->toBeFalse();

    expect($model->fresh())->toBeNull();
});
