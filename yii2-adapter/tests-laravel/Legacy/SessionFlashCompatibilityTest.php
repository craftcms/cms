<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Flash;
use CraftCms\Yii2Adapter\Web\Session;
use Illuminate\Support\ViewErrorBag;

it('reads messages flashed through the Laravel session on the next request', function() {
    $session = Craft::$app->getSession();
    $session->open();

    Flash::success('Your message has been sent.');
    session()->flash('errors', new ViewErrorBag());
    session()->ageFlashData();

    expect($session->hasFlash('success'))->toBeTrue()
        ->and($session->hasFlash('error'))->toBeFalse()
        ->and($session->getAllFlashes())->toBe(['success' => 'Your message has been sent.'])
        ->and($session->getFlash('success', null, true))->toBe('Your message has been sent.')
        ->and($session->hasFlash('success'))->toBeFalse()
        ->and(session('success'))->toBeNull();
});

it('reads flash messages without writing to an empty session', function() {
    $store = session()->driver();
    $store->start();
    $attributes = $store->all();

    $session = new Session();

    expect($session->getFlash('notice'))->toBeNull()
        ->and($session->getAllFlashes(true))->toBe([])
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
