<?php

use CraftCms\Cms\Auth\RememberedUsername;
use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cookie;

test('remembered username', function () {
    expect(RememberedUsername::get())->toBeNull();

    $user = User::findOne();

    RememberedUsername::set($user);

    expect(Cookie::hasQueued(RememberedUsername::cookieName()))->toBeTrue();

    $cookie = Cookie::queued(RememberedUsername::cookieName());

    expect($cookie->getValue())->toBe($user->username);
    expect($cookie->getExpiresTime())->toBe(now()->timestamp + Cms::config()->rememberUsernameDuration);

    // Setting to 0 will remove the cookie.
    Cms::config()->rememberUsernameDuration = 0;

    RememberedUsername::set($user);

    expect(Cookie::hasQueued(RememberedUsername::cookieName()))->toBeFalse();
});
