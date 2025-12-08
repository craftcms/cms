<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\AddressesController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());
});

it('can list fields with a namespace and countryCode', function () {
    postJson(action([AddressesController::class, 'fields'], [
        'namespace' => 'test',
        'countryCode' => 'US',
    ]))->assertOk()->assertJsonStructure([
        'fieldsHtml',
        'headHtml',
        'bodyHtml',
    ]);
});

it('can save a field layout')->todo('When field layouts are ported.');
