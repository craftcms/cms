<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Flash;
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
