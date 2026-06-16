<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Http\Controllers\Users\AddressesController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;

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

test('index', function () {
    get(action([AddressesController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Addresses'));
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
