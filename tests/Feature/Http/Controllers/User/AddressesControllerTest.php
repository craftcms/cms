<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Http\Controllers\Users\AddressesController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    get(action([AddressesController::class, 'index']))->assertRedirect(Cms::config()->cpTrigger.'/login');
    postJson(action([AddressesController::class, 'store']))->assertUnauthorized();
    postJson(action([AddressesController::class, 'destroy']))->assertUnauthorized();
});

test('index shows addresses page for own account', function () {
    postJson(action([AddressesController::class, 'store']), [
        'userId' => auth()->id(),
        'title' => 'Home',
        'addressLine1' => '123 Fake Street',
        'administrativeArea' => 'CA',
        'locality' => 'San Francisco',
        'postalCode' => '94107',
    ])->assertOk();

    get(cp_url('myaccount/addresses'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Addresses')
            ->where('content', fn (string $content) => str_contains($content, t('Addresses')) && str_contains($content, 'action-btn'))
            ->where('headHtml', fn (string $headHtml) => ! str_contains($headHtml, 'legacy/cp/dist/cp.js'))
            ->where('bodyHtml', fn (string $bodyHtml) => str_contains($bodyHtml, 'new Craft.NestedElementManager'))
            ->has('subnav')
            ->has('details'));
});

test('index shows addresses page for other users', function () {
    Edition::set(Edition::Pro);

    $otherUser = UserModel::factory()->createElement();

    get(cp_url("users/{$otherUser->id}/addresses"))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Addresses')
            ->has('content')
            ->has('subnav')
            ->has('details'));
});

test('users can create addresses for their own account without editUsers', function () {
    $user = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement();

    actingAs($user);

    get(cp_url('myaccount/addresses'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('bodyHtml', fn (string $bodyHtml) => str_contains($bodyHtml, '"canCreate":true')));
});

test('users without editUsers cannot create addresses for other users', function () {
    Edition::set(Edition::Pro);

    $user = UserModel::factory()
        ->withPermissions(['accessCp', 'viewUsers'])
        ->createElement();
    $otherUser = UserModel::factory()->createElement();

    actingAs($user);

    get(cp_url("users/{$otherUser->id}/addresses"))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('bodyHtml', fn (string $bodyHtml) => str_contains($bodyHtml, '"canCreate":false')));
});

test('store & destroy', function () {
    expect(new AddressQuery()->count())->toBe(0);

    postJson(action([AddressesController::class, 'store']), [
        'userId' => auth()->id(),
        'firstName' => 'John',
        'lastName' => 'Doe',
        'title' => 'Home',
        'addressLine1' => '123 Fake Street',
        'administrativeArea' => 'CA',
        'locality' => 'San Francisco',
        'postalCode' => '94107',
    ])->assertOk();

    expect(new AddressQuery()->count())->toBe(1);

    postJson(action([AddressesController::class, 'destroy']), [
        'addressId' => DB::table(Table::ADDRESSES)->first()->id,
    ])->assertOk();

    expect(new AddressQuery()->count())->toBe(0);
});

test('store clears existing address fields', function () {
    postJson(action([AddressesController::class, 'store']), [
        'userId' => auth()->id(),
        'firstName' => 'John',
        'lastName' => 'Doe',
        'title' => 'Home',
        'addressLine1' => '123 Fake Street',
        'addressLine2' => 'Suite 100',
        'administrativeArea' => 'CA',
        'locality' => 'San Francisco',
        'postalCode' => '94107',
    ])->assertOk();

    $address = DB::table(Table::ADDRESSES)->first();

    postJson(action([AddressesController::class, 'store']), [
        'userId' => auth()->id(),
        'addressId' => $address->id,
        'firstName' => '',
        'lastName' => '',
        'addressLine2' => '',
    ])->assertOk();

    $address = DB::table(Table::ADDRESSES)->where('id', $address->id)->first();

    expect($address->firstName)->toBeNull()
        ->and($address->lastName)->toBeNull()
        ->and($address->addressLine2)->toBeNull();
});

test('store ignores ownership attributes', function () {
    $user = currentUser();
    $otherUser = UserModel::factory()->createElement();

    postJson(action([AddressesController::class, 'store']), [
        'userId' => $user->id,
        'primaryOwnerId' => $otherUser->id,
        'fieldId' => 1,
        'title' => 'Home',
        'addressLine1' => '123 Fake Street',
        'administrativeArea' => 'CA',
        'locality' => 'San Francisco',
        'postalCode' => '94107',
    ])->assertOk();

    $address = DB::table(Table::ADDRESSES)->first();

    expect($address->primaryOwnerId)->toBe($user->id)
        ->and($address->fieldId)->toBeNull();
});
