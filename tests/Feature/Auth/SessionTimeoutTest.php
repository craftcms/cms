<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionTimeout;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Auth\SessionInfoController;
use CraftCms\Cms\Http\Controllers\Elements\ElementActivityController;
use CraftCms\Cms\Http\Controllers\QueueController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\craftAuth;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\travel;

function sessionExpiry(): int
{
    return now()->addMinutes((int) config('session.lifetime'))->getTimestamp();
}

beforeEach(function () {
    Date::setTestNow(now()->startOfSecond());
});

test('signed-in requests report when the session will expire', function () {
    actingAs(User::findOne());

    get(cp_url('dashboard'))
        ->assertHeader(SessionTimeout::HEADER, (string) sessionExpiry());
});

test('session info reports the expiry without extending it', function () {
    actingAs(User::findOne());
    get(cp_url('dashboard'));
    $expiresAt = sessionExpiry();

    travel(5)->minutes();

    $this->withCookie(config('session.cookie'), session()->getId())
        ->getJson(action([SessionInfoController::class, 'show']))
        ->assertOk()
        ->assertHeaderMissing(SessionTimeout::HEADER)
        ->assertJson(['sessionExpiresAt' => $expiresAt]);
});

test('renewing the session pushes its expiry back', function () {
    actingAs(User::findOne());
    get(cp_url('dashboard'));

    travel(5)->minutes();

    postJson(action([SessionInfoController::class, 'renew']))
        ->assertOk()
        ->assertHeader(SessionTimeout::HEADER, (string) sessionExpiry())
        ->assertJson(['sessionExpiresAt' => sessionExpiry()]);
});

test('renewing the session requires a signed-in user', function () {
    postJson(action([SessionInfoController::class, 'renew']))
        ->assertUnauthorized();
});

test('a remembered session reports no expiry', function () {
    actingAs(User::findOne());

    $this->withCookie(craftAuth()->getRecallerName(), 'remembered')
        ->get(cp_url('dashboard'))
        ->assertHeaderMissing(SessionTimeout::HEADER);
});

test('guests have no session expiry', function () {
    getJson(action([SessionInfoController::class, 'show']))
        ->assertOk()
        ->assertJson(['isGuest' => true, 'sessionExpiresAt' => null]);
});

test('polling for queue progress doesn’t extend the session', function () {
    actingAs(User::findOne());

    getJson(action([QueueController::class, 'jobInfo']))
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'))
        ->assertHeaderMissing(SessionTimeout::HEADER);
});

test('polling for recent element activity doesn’t extend the session', function () {
    actingAs(User::findOne());
    $entry = EntryModel::factory()->createElement();

    postJson(action(ElementActivityController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'))
        ->assertHeaderMissing(SessionTimeout::HEADER);
});
