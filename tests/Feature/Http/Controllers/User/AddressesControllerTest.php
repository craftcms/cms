<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Http\Controllers\Users\AddressesController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
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

test('index cards carry the nested actions the shared manager runs', function () {
    postJson(action([AddressesController::class, 'store']), [
        'userId' => auth()->id(),
        'title' => 'Home',
        'addressLine1' => '123 Fake Street',
        'administrativeArea' => 'CA',
        'locality' => 'San Francisco',
        'postalCode' => '94107',
    ])->assertOk();

    get(action([AddressesController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Addresses')
            ->where('addresses.cards.0.actionMenuItems', function (Collection $items): bool {
                $items = $items->toArray();
                $attributes = collect($items)->pluck('attributes.data')->filter()->collapse();
                $actionNames = collect($items)->pluck('action.name')->filter()->unique()->values()->all();

                return $attributes->has('duplicate-action')
                    && $attributes->has('delete-action')
                    && $actionNames === ['craft:nested-element-action'];
            })
            ->where('addresses.cards.0.capabilities', [
                'copyable' => true,
                'duplicatable' => true,
                'deletable' => true,
            ]));
});

test('index renders the Inertia addresses page', function () {
    get(action([AddressesController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Addresses')
            ->where('userId', auth()->id())
            ->where('showIndex', false)
            ->where('editable', true)
            ->where('title', t('Addresses'))
            ->has('crumbs', 3)
            // A list, not an object keyed by screen name: the shell hides the
            // secondary nav when it can't count the items.
            ->where('subnav.0.label', t('Profile'))
            ->has('details')
            ->where('addresses.viewMode', 'cards-grid')
            ->where('addresses.manager.attribute', 'addresses')
            ->where('addresses.manager.elementType', Address::class)
            ->where('addresses.manager.showInGrid', true)
            ->has('addresses.cards', 0));
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

test('store rejects a non-integer address id', function () {
    postJson(action([AddressesController::class, 'store']), [
        'userId' => auth()->id(),
        'addressId' => ['ownerId' => auth()->id()],
    ])->assertJsonValidationErrors(['addressId']);
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

test('duplicates a user address after opening the Addresses screen', function () {
    $user = User::findOne();
    $address = new Address([
        'ownerId' => $user->id,
        'title' => 'Home',
        'countryCode' => 'US',
        'addressLine1' => '123 Fake Street',
        'administrativeArea' => 'CA',
        'locality' => 'San Francisco',
        'postalCode' => '94107',
    ]);
    expect(Elements::saveElement($address))->toBeTrue();

    $response = getJson(action([AddressesController::class, 'index']), ['X-Inertia' => 'true'])->assertOk();

    postJson(action(PerformElementActionController::class), [
        ...$response->json('props.addresses.manager'),
        'elementAction' => Duplicate::class,
        'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
        'source' => '__IMP__',
        'elementIds' => [$address->id],
    ])->assertOk();

    expect(Address::find()->ownerId($user->id)->count())->toBe(2);
});
