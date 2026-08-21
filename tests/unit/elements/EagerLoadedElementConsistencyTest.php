<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\db\EagerLoadPlan;
use craft\elements\Entry;
use craft\elements\User;
use craft\test\TestCase;
use UnitTester;

/**
 * Ensures that `getEagerLoadedElements()`/`hasEagerLoadedElements()` stay in sync with the
 * dedicated getter methods (`getOwner()`, `getPrimaryOwner()`, `getUploader()`, `getAuthor()`,
 * `getAuthors()`, `getPhoto()`) once eager-loaded elements have been applied to an element.
 *
 * Prior to the fix, `setEagerLoadedElements()` implementations for these special-cased handles
 * would return early rather than also calling their parent implementation, meaning
 * `getEagerLoadedElements()`/`hasEagerLoadedElements()` wouldn’t reflect elements that had
 * clearly been eager-loaded (as evidenced by the dedicated getter methods).
 *
 * @see https://github.com/craftcms/cms/pull/19468
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class EagerLoadedElementConsistencyTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider ownerHandleDataProvider
     */
    public function testOwnerEagerLoading(string $handle): void
    {
        $owner = new Entry(['id' => 100]);
        $nested = new Entry(['id' => 1]);
        $plan = new EagerLoadPlan(['handle' => $handle]);

        $nested->setEagerLoadedElements($handle, [$owner], $plan);

        self::assertTrue($nested->hasEagerLoadedElements($handle));

        $eagerLoaded = $nested->getEagerLoadedElements($handle);
        self::assertNotNull($eagerLoaded);
        self::assertSame([$owner], $eagerLoaded->all());

        $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
        self::assertSame($owner, $getter);
    }

    public static function ownerHandleDataProvider(): array
    {
        return [
            'owner' => ['owner'],
            'primaryOwner' => ['primaryOwner'],
        ];
    }

    /**
     * @dataProvider emptyOwnerHandleDataProvider
     */
    public function testOwnerEagerLoadingWithNoOwner(string $handle): void
    {
        $nested = new Entry(['id' => 1]);
        $plan = new EagerLoadPlan(['handle' => $handle]);

        $nested->setEagerLoadedElements($handle, [], $plan);

        self::assertTrue($nested->hasEagerLoadedElements($handle));
        self::assertEmpty($nested->getEagerLoadedElements($handle));

        $getter = $handle === 'owner' ? $nested->getOwner() : $nested->getPrimaryOwner();
        self::assertNull($getter);
    }

    public static function emptyOwnerHandleDataProvider(): array
    {
        return [
            'owner' => ['owner'],
            'primaryOwner' => ['primaryOwner'],
        ];
    }

    public function testUploaderEagerLoading(): void
    {
        $uploader = new User(['id' => 200]);
        $asset = new Asset(['id' => 2]);
        $plan = new EagerLoadPlan(['handle' => 'uploader']);

        $asset->setEagerLoadedElements('uploader', [$uploader], $plan);

        self::assertTrue($asset->hasEagerLoadedElements('uploader'));

        $eagerLoaded = $asset->getEagerLoadedElements('uploader');
        self::assertNotNull($eagerLoaded);
        self::assertSame([$uploader], $eagerLoaded->all());

        self::assertSame($uploader, $asset->getUploader());
    }

    public function testUploaderEagerLoadingWithNoUploader(): void
    {
        $asset = new Asset(['id' => 2]);
        $plan = new EagerLoadPlan(['handle' => 'uploader']);

        $asset->setEagerLoadedElements('uploader', [], $plan);

        self::assertTrue($asset->hasEagerLoadedElements('uploader'));
        self::assertEmpty($asset->getEagerLoadedElements('uploader'));
        self::assertNull($asset->getUploader());
    }

    public function testPhotoEagerLoading(): void
    {
        $photo = new Asset(['id' => 300]);
        $user = new User(['id' => 3]);
        $plan = new EagerLoadPlan(['handle' => 'photo']);

        $user->setEagerLoadedElements('photo', [$photo], $plan);

        self::assertTrue($user->hasEagerLoadedElements('photo'));

        $eagerLoaded = $user->getEagerLoadedElements('photo');
        self::assertNotNull($eagerLoaded);
        self::assertSame([$photo], $eagerLoaded->all());

        self::assertSame($photo, $user->getPhoto());
    }

    public function testPhotoEagerLoadingWithNoPhoto(): void
    {
        $user = new User(['id' => 3]);
        $plan = new EagerLoadPlan(['handle' => 'photo']);

        $user->setEagerLoadedElements('photo', [], $plan);

        self::assertTrue($user->hasEagerLoadedElements('photo'));
        self::assertEmpty($user->getEagerLoadedElements('photo'));
        self::assertNull($user->getPhoto());
    }

    public function testAuthorEagerLoading(): void
    {
        $author = new User(['id' => 400]);
        $entry = new Entry(['id' => 4]);
        $plan = new EagerLoadPlan(['handle' => 'author']);

        $entry->setEagerLoadedElements('author', [$author], $plan);

        self::assertTrue($entry->hasEagerLoadedElements('author'));

        $eagerLoaded = $entry->getEagerLoadedElements('author');
        self::assertNotNull($eagerLoaded);
        self::assertSame([$author], $eagerLoaded->all());

        self::assertSame($author, $entry->getAuthor());
        self::assertSame([$author], $entry->getAuthors());
    }

    public function testAuthorsEagerLoading(): void
    {
        $author1 = new User(['id' => 401]);
        $author2 = new User(['id' => 402]);
        $entry = new Entry(['id' => 4]);
        $plan = new EagerLoadPlan(['handle' => 'authors']);

        $entry->setEagerLoadedElements('authors', [$author1, $author2], $plan);

        self::assertTrue($entry->hasEagerLoadedElements('authors'));

        $eagerLoaded = $entry->getEagerLoadedElements('authors');
        self::assertNotNull($eagerLoaded);
        self::assertSame([$author1, $author2], $eagerLoaded->all());

        self::assertSame($author1, $entry->getAuthor());
        self::assertSame([$author1, $author2], $entry->getAuthors());
    }

    public function testAuthorsEagerLoadingWithNoAuthors(): void
    {
        $entry = new Entry(['id' => 4]);
        $plan = new EagerLoadPlan(['handle' => 'authors']);

        $entry->setEagerLoadedElements('authors', [], $plan);

        self::assertTrue($entry->hasEagerLoadedElements('authors'));
        self::assertEmpty($entry->getEagerLoadedElements('authors'));

        self::assertNull($entry->getAuthor());
        self::assertSame([], $entry->getAuthors());
    }

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

        self::assertTrue($user->hasEagerLoadedElements('addresses'));

        // Check the `User::_addresses` property is set correctly
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('_addresses');
        $property->setAccessible(true);
        // Check it has been initialized with the eager-loaded addresses
        self::assertTrue($property->isInitialized($user));

        $eagerLoaded = $user->getEagerLoadedElements('addresses');
        $propertyGetValue = $property->getValue($user);
        $userAddresses = $user->getAddresses();
        self::assertNotNull($eagerLoaded);
        self::assertNotNull($propertyGetValue);
        self::assertNotNull($userAddresses);
        self::assertCount(2, $eagerLoaded);
        self::assertCount(2, $propertyGetValue);
        self::assertCount(2, $userAddresses);

        foreach ([$eagerLoaded->all(), $propertyGetValue->all(), $userAddresses->all()] as $a) {
            // Check each address has data matching original `$addressData`
            foreach ($a as $index => $address) {
                foreach ($addressData[$index] as $key => $value) {
                    self::assertSame($value, $address->$key);
                }
            }
        }
    }
}
