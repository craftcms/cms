<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Flash;
use Illuminate\Support\Facades\Session;

it('registers flash accessors as closures that can be rebound by Laravel macros', function () {
    session()->flash('error', 'Could not sign in.');
    session()->flash('notice', 'Check your email.');
    session()->flash('success', 'Signed in.');

    expect(Session::getError())->toBe(Flash::getError())
        ->and(Session::getNotice())->toBe(Flash::getNotice())
        ->and(Session::getSuccess())->toBe(Flash::getSuccess());
});
