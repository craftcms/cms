<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Auth\SessionInfoController;

use function Pest\Laravel\getJson;

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
