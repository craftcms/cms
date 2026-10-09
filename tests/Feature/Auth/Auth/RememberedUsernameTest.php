<?php

use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cookie;

test('remembered username', function () {
    expect(app(AuthMethods::class)->getRememberedUsername())->toBeNull();

    $user = User::findOne();

    app(AuthMethods::class)->setRememberedUsername($user);

    expect(Cookie::hasQueued(app(AuthMethods::class)->rememberedUsernameCookie()))->toBeTrue();

    $cookie = Cookie::queued(app(AuthMethods::class)->rememberedUsernameCookie());

    expect($cookie->getValue())->toBe($user->username);
    expect($cookie->getExpiresTime())->toBe(now()->timestamp + Cms::config()->rememberUsernameDuration);

    // Setting to 0 will remove the cookie.
    Cms::config()->rememberUsernameDuration = 0;

    app(AuthMethods::class)->setRememberedUsername($user);

    expect(Cookie::hasQueued(app(AuthMethods::class)->rememberedUsernameCookie()))->toBeFalse();
});
