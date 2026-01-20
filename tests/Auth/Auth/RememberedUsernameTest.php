<?php

use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cookie;

test('remembered username', function () {
    expect(app(Auth::class)->getRememberedUsername())->toBeNull();

    $user = User::findOne();

    app(Auth::class)->setRememberedUsername($user);

    expect(Cookie::hasQueued(app(Auth::class)->rememberedUsernameCookie()))->toBeTrue();

    $cookie = Cookie::queued(app(Auth::class)->rememberedUsernameCookie());

    expect($cookie->getValue())->toBe($user->username);
    expect($cookie->getExpiresTime())->toBe(now()->timestamp + Cms::config()->rememberUsernameDuration);

    // Setting to 0 will remove the cookie.
    Cms::config()->rememberUsernameDuration = 0;

    app(Auth::class)->setRememberedUsername($user);

    expect(Cookie::hasQueued(app(Auth::class)->rememberedUsernameCookie()))->toBeFalse();
});
