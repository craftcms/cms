<?php

declare(strict_types=1);

use CraftCms\Yii2Adapter\Web\Session;

it('reads flash messages without writing to an empty session', function() {
    $store = session()->driver();
    $store->start();
    $attributes = $store->all();

    $session = new Session();

    expect($session->getFlash('notice'))->toBeNull()
        ->and($store->all())->toBe($attributes);
});

it('still ages flash messages', function() {
    $store = session()->driver();
    $store->start();

    new Session()->setFlash('notice', 'Saved', removeAfterAccess: false);
    new Session();

    expect($store->get('__yii_flash'))->toBe(['notice' => 1])
        ->and($store->get('notice'))->toBe('Saved');
});
