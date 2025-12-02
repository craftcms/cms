<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\RouteToken\Model\RouteToken;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Json;

beforeEach(function () {
    $this->tokens = resolve(RouteTokens::class);
});

it('can create tokens', function () {
    $token = $this->tokens->createToken('do/stuff', 1, $expiryDate = now()->addDay());

    $tokenModel = RouteToken::where('token', $token)->firstOrFail();

    expect($tokenModel->route)->toBe('do/stuff');
    expect($tokenModel->usageLimit)->toBe(1);
    expect($tokenModel->usageCount)->toBe(0);
    expect($tokenModel->expiryDate->isSameDay($expiryDate))->toBeTrue();
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

    $expiryDate = now()->addSeconds(10000);

    $token = $this->tokens->createToken('do/stuff');

    expect(strlen((string) $token))->toBe(32);

    $model = RouteToken::where('token', $token)->firstOrFail();

    expect($model->usageLimit)->toBeNull();
    expect($model->usageCount)->toBeNull();
    expect($model->expiryDate->isSameMinute($expiryDate))->toBeTrue();
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
