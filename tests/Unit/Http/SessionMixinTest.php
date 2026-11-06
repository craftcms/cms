<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Providers\AppServiceProvider;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Cms::config()->cpTrigger = 'admin';
});

it('registers flash accessors as closures that can be rebound by Laravel macros', function () {
    session()->flash('error', 'Could not sign in.');
    session()->flash('notice', 'Check your email.');
    session()->flash('success', 'Signed in.');

    expect(Session::getError())->toBe(Flash::getError())
        ->and(Session::getNotice())->toBe(Flash::getNotice())
        ->and(Session::getSuccess())->toBe(Flash::getSuccess());
});

it('registers session macros without resolving the configured session driver', function () {
    $originalSession = app(SessionManager::class);

    app()->instance('session', new class
    {
        public function mixin(): never
        {
            throw new RuntimeException('Session driver was resolved.');
        }
    });

    Session::clearResolvedInstance('session');

    try {
        new AppServiceProvider(app())->register();

        expect(SessionStore::hasMacro('getError'))->toBeTrue();
    } finally {
        app()->instance('session', $originalSession);
        Session::clearResolvedInstance('session');
    }
});

it('does not queue broadcast messages outside the control panel', function () {
    app()->instance('request', Request::create('/news'));

    session()->broadcastToJs([
        'event' => 'saveElement',
        'id' => 42,
    ]);

    expect(session()->getJs(false))->toBe([]);
});

it('flashes broadcast messages as scripts for pages that render flashed JS', function () {
    app()->instance('request', Request::create('/admin/dashboard'));

    session()->broadcastToJs(['event' => 'saveElement', 'id' => 42]);

    expect(session()->getJs())->toBe([[
        "window.dispatchEvent(new CustomEvent('craft:broadcast', {detail: {\"event\":\"saveElement\",\"id\":42}}));",
        Position::Ready->value,
        null,
    ]])
        ->and(session()->getBroadcasts())->toBe([]);
});
