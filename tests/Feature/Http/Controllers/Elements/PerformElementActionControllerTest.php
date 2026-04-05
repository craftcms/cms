<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->performElementAction = fn (array $payload = []) => postJson(
        action(PerformElementActionController::class),
        array_merge([
            'context' => 'index',
            'source' => '*',
            'viewState' => [
                'mode' => 'table',
                'static' => false,
            ],
        ], $payload),
    );
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action(PerformElementActionController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('validates required request params', function () {
    ($this->performElementAction)([
        'elementType' => Entry::class,
    ])->assertUnprocessable();
});

it('returns 400 for unsupported actions', function () {
    ($this->performElementAction)([
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [1],
        'source' => null,
    ])->assertStatus(400);
});
