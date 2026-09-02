<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('passkey-endpoints signals passkey support without leaking the CP URL', function () {
    get('.well-known/passkey-endpoints')
        ->assertOk()
        ->assertHeader('content-type', 'application/json')
        ->assertContent('{}');
});
