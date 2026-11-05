<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use craft\elements\Address;
use craft\elements\db\EagerLoadPlan;
use craft\elements\Entry;
use craft\elements\User;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for the Address element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class AddressElementTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function testAddressesEagerLoading(): void
    {
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
        $addresses = array_map(fn($data) => new Address($data), $addressData);

        $plan = new EagerLoadPlan(['handle' => 'addresses']);

        $user->setEagerLoadedElements('addresses', $addresses, $plan);

        // `addresses` is handled directly by `User::setEagerLoadedElements()`, populating the
        // private `_addresses` property rather than the generic eager-loaded elements array
        // (which is what keeps `$user->addresses` consistent with `getAddresses()` — see
        // `testAddressesPropertyMatchesGetter()`), so `hasEagerLoadedElements()`/
        // `getEagerLoadedElements()` don’t reflect it.
        self::assertFalse($user->hasEagerLoadedElements('addresses'));

        // Check the `User::_addresses` property is set correctly
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('_addresses');
        $property->setAccessible(true);
        // Check it has been initialized with the eager-loaded addresses
        self::assertTrue($property->isInitialized($user));

        $propertyGetValue = $property->getValue($user)->all();
        $userAddresses = $user->getAddresses()->all();
        self::assertNotEmpty($propertyGetValue);
        self::assertNotEmpty($userAddresses);
        self::assertCount(2, $propertyGetValue);
        self::assertCount(2, $userAddresses);

        foreach ([$propertyGetValue, $userAddresses] as $a) {
            // Check each address has data matching original `$addressData`
            foreach ($a as $index => $address) {
                foreach ($addressData[$index] as $key => $value) {
                    self::assertSame($value, $address->$key);
                }
            }
        }
    }

    /**
     * `owner`/`primaryOwner` are magic properties that resolve to `getOwner()`/`getPrimaryOwner()`
     * (via `NestedElementTrait`). Since eager-loading those handles bypasses the generic
     * `Element::$_eagerLoadedElements` array (see `NestedElementTrait::setEagerLoadedElements()`),
     * property access should always match the dedicated getters, whether or not the owner has
     * been eager-loaded.
     *
     * @dataProvider ownerHandleDataProvider
     */
    public function testOwnerPropertyMatchesGetter(string $handle): void
    {
        $address = new Address(['id' => 1]);

        // No owner set at all
        $getter = $handle === 'owner' ? $address->getOwner() : $address->getPrimaryOwner();
        self::assertNull($getter);
        self::assertSame($getter, $address->$handle);

        // Owner set directly, not via eager-loading
        $owner = new User(['id' => 100]);
        if ($handle === 'owner') {
            $address->setOwner($owner);
        } else {
            $address->setPrimaryOwner($owner);
        }
        $getter = $handle === 'owner' ? $address->getOwner() : $address->getPrimaryOwner();
        self::assertSame($owner, $getter);
        self::assertSame($getter, $address->$handle);

        // Owner eager-loaded
        $eagerLoadedOwner = new Entry(['id' => 101]);
        $plan = new EagerLoadPlan(['handle' => $handle]);
        $address2 = new Address(['id' => 2]);
        $address2->setEagerLoadedElements($handle, [$eagerLoadedOwner], $plan);
        $getter = $handle === 'owner' ? $address2->getOwner() : $address2->getPrimaryOwner();
        self::assertSame($eagerLoadedOwner, $getter);
        self::assertSame($getter, $address2->$handle);

        // No owner eager-loaded (empty result)
        $address3 = new Address(['id' => 3]);
        $address3->setEagerLoadedElements($handle, [], $plan);
        $getter = $handle === 'owner' ? $address3->getOwner() : $address3->getPrimaryOwner();
        self::assertNull($getter);
        self::assertSame($getter, $address3->$handle);
    }

    public static function ownerHandleDataProvider(): array
    {
        return [
            'owner' => ['owner'],
            'primaryOwner' => ['primaryOwner'],
        ];
    }
}
