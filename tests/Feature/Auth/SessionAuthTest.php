<?php

use CraftCms\Cms\Auth\SessionAuth;

test('it can authorize actions', function () {
    expect(SessionAuth::checkAuthorization('foo'))->toBeFalse();

    SessionAuth::authorize('foo');

    expect(SessionAuth::checkAuthorization('foo'))->toBeTrue();

    SessionAuth::deauthorize('foo');

    expect(SessionAuth::checkAuthorization('foo'))->toBeFalse();
});
