<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\User\Elements\User;

/**
 * `photo` is a magic property that resolves to `getPhoto()`. Since eager-loading the `photo`
 * handle bypasses the generic `Element::$_eagerLoadedElements` array (see
 * `User::setEagerLoadedElements()`), property access should always match `getPhoto()`, whether or
 * not the photo has been eager-loaded.
 */
test('photo property matches getPhoto()', function () {
    // No photo set at all
    $user = new User(['id' => 600]);
    expect($user->getPhoto())->toBeNull()->and($user->photo)->toBe($user->getPhoto());

    // Photo set directly, not via eager-loading
    $photo = new Asset(['id' => 601]);
    $user->setPhoto($photo);
    expect($user->getPhoto())->toBe($photo)->and($user->photo)->toBe($user->getPhoto());

    // Photo eager-loaded
    $user2 = new User(['id' => 602]);
    $plan = new EagerLoadPlan(handle: 'photo');
    $user2->setEagerLoadedElements('photo', [$photo], $plan);
    expect($user2->getPhoto())->toBe($photo)->and($user2->photo)->toBe($user2->getPhoto());

    // No photo eager-loaded (empty result)
    $user3 = new User(['id' => 603]);
    $user3->setEagerLoadedElements('photo', [], $plan);
    expect($user3->getPhoto())->toBeNull()->and($user3->photo)->toBe($user3->getPhoto());
});

/**
 * `addresses` is handled directly by `User::setEagerLoadedElements()`, populating the private
 * `_addresses` property rather than the generic eager-loaded elements array, so
 * `hasEagerLoadedElements()`/`getEagerLoadedElements()` don't reflect it.
 */
test('addresses eager-loading populates the private property directly', function () {
    $user = new User(['id' => 401]);
    $addressData = [
        [
            'id' => 9004,
            'countryCode' => 'US',
            'addressLine1' => '123 Main St',
            'administrativeArea' => 'CA',
            'locality' => 'Los Angeles',
            'postalCode' => '90001',
        ],
        [
            'id' => 9005,
            'countryCode' => 'US',
            'addressLine1' => '456 Elm St',
            'administrativeArea' => 'CA',
            'locality' => 'Los Angeles',
            'postalCode' => '90002',
        ],
    ];
    $addresses = array_map(fn (array $data) => new Address($data), $addressData);
    $plan = new EagerLoadPlan(handle: 'addresses');

    $user->setEagerLoadedElements('addresses', $addresses, $plan);

    expect($user->hasEagerLoadedElements('addresses'))->toBeFalse();

    // Check the `User::_addresses` property is set correctly
    $reflection = new ReflectionClass($user);
    $property = $reflection->getProperty('_addresses');
    // Check it has been initialized with the eager-loaded addresses
    expect($property->isInitialized($user))->toBeTrue();

    $propertyGetValue = $property->getValue($user)->all();
    $userAddresses = $user->getAddresses()->all();
    expect($propertyGetValue)->toHaveCount(2)
        ->and($userAddresses)->toHaveCount(2);

    foreach ([$propertyGetValue, $userAddresses] as $a) {
        // Check each address has data matching original `$addressData`
        foreach ($a as $index => $address) {
            foreach ($addressData[$index] as $key => $value) {
                expect($address->$key)->toBe($value);
            }
        }
    }
});

/**
 * `addresses` is a magic property that resolves to `getAddresses()`. Property access should
 * always match `getAddresses()`, whether or not the addresses have been eager-loaded.
 */
test('addresses property matches getAddresses()', function () {
    // No addresses set at all (a user with no ID has none to query for)
    $user = new User;
    expect($user->getAddresses()->all())->toBe([])->and($user->addresses->all())->toBe([]);

    // Addresses eager-loaded
    $address = new Address(['id' => 9001, 'countryCode' => 'US']);
    $user2 = new User(['id' => 500]);
    $plan = new EagerLoadPlan(handle: 'addresses');
    $user2->setEagerLoadedElements('addresses', [$address], $plan);
    expect($user2->getAddresses()->all())->toBe([$address])->and($user2->addresses->all())->toBe([$address]);

    // No addresses eager-loaded (empty result)
    $user3 = new User(['id' => 501]);
    $user3->setEagerLoadedElements('addresses', [], $plan);
    expect($user3->getAddresses()->all())->toBe([])->and($user3->addresses->all())->toBe([]);
});
