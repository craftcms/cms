<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use Craft;
use craft\elements\Address;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;

/**
 * Tests the address mutations, for GraphQL requests authenticated as a user and for schema tokens.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class AddressMutationsTest extends TestCase
{
    private User $user;
    private User $other;
    private bool $lazyGqlTypes;

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
        ];
    }

    protected function _before(): void
    {
        Craft::$app->edition = CmsEdition::Pro;

        // Prebuilt schemas can only be built once per process
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->lazyGqlTypes = $generalConfig->lazyGqlTypes;
        $generalConfig->lazyGqlTypes = true;

        $this->user = $this->createUser();
        $this->other = $this->createUser();

        // The GraphQL controller sets the authenticated user as the identity
        Craft::$app->getUser()->setIdentity($this->user);
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getConfig()->getGeneral()->lazyGqlTypes = $this->lazyGqlTypes;
        Craft::$app->getGql()->flushCaches();
    }

    public function testEveryAuthenticatedUserCanReachTheMutations(): void
    {
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user);
        self::assertContains('addresses.all:edit', $schema->scope);
        self::assertContains('addresses.all:save', $schema->scope);
        self::assertContains('addresses.all:delete', $schema->scope);

        // Read-only schemas can't mutate
        $schema = Craft::$app->getGql()->getSchemaForUser($this->user, false);
        self::assertNotContains('addresses.all:save', $schema->scope);
        $result = $this->execute($schema, 'mutation { save_Address(countryCode: "US") { id } }');
        self::assertArrayNotHasKey('save_Address', $result['data'] ?? []);
        self::assertEquals(0, Address::find()->primaryOwnerId($this->user->id)->count());
    }

    public function testNewAddressesBelongToTheAuthenticatedUser(): void
    {
        $result = $this->execute($this->schema(), 'mutation { save_Address(title: "Home", countryCode: "US", addressLine1: "1 Main St", locality: "Springfield", administrativeArea: "IL", postalCode: "62701") { id title countryCode addressLine1 } }');

        self::assertArrayNotHasKey('errors', $result);
        $saved = $result['data']['save_Address'];
        self::assertSame('Home', $saved['title']);
        self::assertSame('US', $saved['countryCode']);

        $address = Address::find()->id($saved['id'])->one();
        self::assertSame($this->user->id, $address->getPrimaryOwnerId());
        self::assertNull($address->fieldId);

        // The user can read their own address book without being able to query users
        $result = $this->execute($this->schema(), '{ currentUser { addresses { id title } } }');
        self::assertSame([['id' => $saved['id'], 'title' => 'Home']], $result['data']['currentUser']['addresses']);
    }

    public function testExistingAddressesCanBeUpdated(): void
    {
        $address = $this->createAddress($this->user);

        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, addressLine1: \"2 Side St\") { id addressLine1 locality } }");

        self::assertArrayNotHasKey('errors', $result);
        // Other fields are left alone
        self::assertSame(['id' => (string)$address->id, 'addressLine1' => '2 Side St', 'locality' => 'Springfield'], $result['data']['save_Address']);
        self::assertEquals(1, Address::find()->primaryOwnerId($this->user->id)->count());

        // The owner can't be changed
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, ownerId: {$this->other->id}) { id } }");
        self::assertSame('An address’s owner can’t be changed.', $result['errors'][0]['message']);
        self::assertSame($this->user->id, Address::find()->id($address->id)->one()->getPrimaryOwnerId());
    }

    public function testExistingAddressesCanBeIdentifiedByUid(): void
    {
        $address = $this->createAddress($this->user);

        $result = $this->execute($this->schema(), "mutation { save_Address(uid: \"$address->uid\", addressLine1: \"3 Uid St\") { id addressLine1 } }");

        self::assertArrayNotHasKey('errors', $result, json_encode($result));
        self::assertSame((string)$address->id, $result['data']['save_Address']['id']);
        self::assertSame('3 Uid St', $result['data']['save_Address']['addressLine1']);
        self::assertEquals(1, Address::find()->primaryOwnerId($this->user->id)->count());

        // Unknown UIDs are errors, rather than new addresses
        $result = $this->execute($this->schema(), 'mutation { save_Address(uid: "' . StringHelper::UUID() . '", countryCode: "US") { id } }');
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        self::assertEquals(1, Address::find()->primaryOwnerId($this->user->id)->count());
    }

    public function testCountriesCantBeBlankedOnUpdate(): void
    {
        $address = $this->createAddress($this->user);

        foreach (['null', '""'] as $value) {
            $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, countryCode: $value) { id } }");
            self::assertSame('A country code is required.', $result['errors'][0]['message'], "countryCode: $value");
        }

        self::assertSame('US', Address::find()->id($address->id)->one()->countryCode);

        // But updates can leave it out
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, addressLine1: \"4 Quiet St\") { countryCode } }");
        self::assertSame('US', $result['data']['save_Address']['countryCode']);
    }

    public function testNamesStayConsistentWhenUpdatingOneOfThem(): void
    {
        $address = $this->createAddress($this->user, ['fullName' => 'Pat Doe']);
        $fields = 'fullName firstName lastName';

        // Changing a first or last name replaces the full name
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, firstName: \"Sam\") { $fields } }");
        self::assertArrayNotHasKey('errors', $result, json_encode($result));
        self::assertSame(['fullName' => 'Sam Doe', 'firstName' => 'Sam', 'lastName' => 'Doe'], $result['data']['save_Address']);

        // And changing the full name replaces the first and last
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, fullName: \"Alex Roe\") { $fields } }");
        self::assertArrayNotHasKey('errors', $result, json_encode($result));
        self::assertSame(['fullName' => 'Alex Roe', 'firstName' => 'Alex', 'lastName' => 'Roe'], $result['data']['save_Address']);

        // Leaving names out leaves them alone
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, addressLine1: \"5 Name St\") { $fields } }");
        self::assertSame(['fullName' => 'Alex Roe', 'firstName' => 'Alex', 'lastName' => 'Roe'], $result['data']['save_Address']);
    }

    public function testAddressesNeedACountry(): void
    {
        $result = $this->execute($this->schema(), 'mutation { save_Address(addressLine1: "1 Main St") { id } }');

        self::assertSame('A country code is required.', $result['errors'][0]['message']);
        self::assertEquals(0, Address::find()->primaryOwnerId($this->user->id)->count());
    }

    public function testUsersCantChangeOtherUsersAddresses(): void
    {
        $theirs = $this->createAddress($this->other);

        $result = $this->execute($this->schema(), "mutation { save_Address(id: $theirs->id, addressLine1: \"Hijacked\") { id } }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        self::assertSame('9 Elm St', Address::find()->id($theirs->id)->one()->addressLine1);

        $result = $this->execute($this->schema(), "mutation { deleteAddress(id: $theirs->id) }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        self::assertNotNull(Address::find()->id($theirs->id)->one());

        // Or add addresses for them
        $result = $this->execute($this->schema(), "mutation { save_Address(ownerId: {$this->other->id}, countryCode: \"US\") { id } }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        self::assertEquals(1, Address::find()->primaryOwnerId($this->other->id)->count());

        // Without revealing whether the user exists, with or without the rest of the address
        $result = $this->execute($this->schema(), 'mutation { save_Address(ownerId: 999999, countryCode: "US") { id } }');
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        $result = $this->execute($this->schema(), 'mutation { save_Address(ownerId: 999999) { id } }');
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        $result = $this->execute($this->schema(), "mutation { save_Address(ownerId: {$this->other->id}) { id } }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);

        // Or who an address belongs to
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $theirs->id, ownerId: {$this->user->id}) { id } }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        $result = $this->execute($this->schema(), "mutation { save_Address(id: $theirs->id, ownerId: {$this->other->id}) { id } }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
    }

    public function testUsersWhoCanEditUsersCanChangeTheirAddresses(): void
    {
        Craft::$app->getUserPermissions()->saveUserPermissions($this->user->id, ['viewUsers', 'editUsers']);
        $theirs = $this->createAddress($this->other);
        $editor = Craft::$app->getUsers()->getUserById($this->user->id);
        self::assertTrue($editor->can('editUsers'), 'editUsers permission');
        self::assertTrue(Craft::$app->getElements()->canSave($this->other, $editor), 'can save the other user');
        $theirs = Address::find()->id($theirs->id)->one();
        self::assertTrue(Craft::$app->getElements()->canSave($theirs, $editor), 'can save their address');

        $result = $this->execute($this->schema(), "mutation { save_Address(id: $theirs->id, addressLine1: \"Updated\") { addressLine1 } }");
        self::assertArrayNotHasKey('errors', $result, json_encode($result));
        self::assertSame('Updated', $result['data']['save_Address']['addressLine1']);

        $result = $this->execute($this->schema(), "mutation { save_Address(ownerId: {$this->other->id}, title: \"Toronto\", countryCode: \"CA\", addressLine1: \"1 Queen St\", locality: \"Toronto\", administrativeArea: \"ON\", postalCode: \"M5V 2T6\") { id } }");
        self::assertArrayNotHasKey('errors', $result);
        self::assertEquals(2, Address::find()->primaryOwnerId($this->other->id)->count());

        $result = $this->execute($this->schema(), "mutation { deleteAddress(id: $theirs->id) }");
        self::assertTrue($result['data']['deleteAddress']);
        self::assertNull(Address::find()->id($theirs->id)->one());
    }

    public function testAddressesCanBeDeleted(): void
    {
        $address = $this->createAddress($this->user);

        $result = $this->execute($this->schema(), "mutation { deleteAddress(id: $address->id) }");
        self::assertTrue($result['data']['deleteAddress']);
        self::assertNull(Address::find()->id($address->id)->one());

        // Already gone
        $result = $this->execute($this->schema(), "mutation { deleteAddress(id: $address->id) }");
        self::assertFalse($result['data']['deleteAddress']);
    }

    public function testOnlyAddressBookAddressesCanBeChanged(): void
    {
        // An address that belongs to something other than a user, such as an order’s
        $entry = Entry::find()->one();
        $address = new Address(['countryCode' => 'US', 'addressLine1' => '5 Order Rd']);
        $address->setPrimaryOwner($entry);
        $address->setOwner($entry);
        Craft::$app->getElements()->saveElement($address, false);

        Craft::$app->getUserPermissions()->saveUserPermissions($this->user->id, ['viewUsers', 'editUsers']);

        $result = $this->execute($this->schema(), "mutation { save_Address(id: $address->id, addressLine1: \"Nope\") { id } }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);

        $result = $this->execute($this->schema(), "mutation { deleteAddress(id: $address->id) }");
        self::assertSame('Unable to perform the action.', $result['errors'][0]['message']);
        self::assertSame('5 Order Rd', Address::find()->id($address->id)->one()->addressLine1);
    }

    public function testSchemaTokensMustSayWhoTheAddressIsFor(): void
    {
        $schema = new GqlSchema([
            'name' => 'Token',
            'uid' => StringHelper::UUID(),
            'scope' => ['addresses.all:save', 'addresses.all:delete'],
        ]);
        Craft::$app->getUser()->setIdentity(null);

        $result = $this->execute($schema, 'mutation { save_Address(countryCode: "US") { id } }');
        self::assertSame('`ownerId` is required.', $result['errors'][0]['message']);

        $result = $this->execute($schema, "mutation { save_Address(ownerId: {$this->other->id}, title: \"Token\", countryCode: \"US\", addressLine1: \"3 Token Ln\", locality: \"Springfield\", administrativeArea: \"IL\", postalCode: \"62701\") { id addressLine1 } }");
        self::assertArrayNotHasKey('errors', $result, json_encode($result));
        $id = $result['data']['save_Address']['id'];
        self::assertSame($this->other->id, Address::find()->id($id)->one()->getPrimaryOwnerId());

        $result = $this->execute($schema, "mutation { deleteAddress(id: $id) }");
        self::assertTrue($result['data']['deleteAddress']);

        // Without the components, there are no mutations
        $schema = new GqlSchema(['name' => 'Token', 'uid' => StringHelper::UUID(), 'scope' => ['addresses.all:delete']]);
        $result = $this->execute($schema, 'mutation { save_Address(ownerId: 1, countryCode: "US") { id } }');
        self::assertArrayNotHasKey('save_Address', $result['data'] ?? []);
    }

    private function schema(): GqlSchema
    {
        return Craft::$app->getGql()->getSchemaForUser(Craft::$app->getUsers()->getUserById($this->user->id));
    }

    private function createUser(): User
    {
        $user = new User([
            'username' => 'gql-' . StringHelper::randomString(6),
            'email' => 'gql-' . StringHelper::randomString(6) . '@example.com',
            'active' => true,
        ]);
        Craft::$app->getElements()->saveElement($user, false);

        return Craft::$app->getUsers()->getUserById($user->id);
    }

    private function createAddress(User $owner, array $attributes = []): Address
    {
        $address = new Address($attributes + [
            'countryCode' => 'US',
            'addressLine1' => '9 Elm St',
            'locality' => 'Springfield',
            'administrativeArea' => 'IL',
            'postalCode' => '62701',
        ]);
        $address->title = 'Address';
        $address->setPrimaryOwner($owner);
        $address->setOwner($owner);
        Craft::$app->getElements()->saveElement($address, false);

        return $address;
    }

    private function execute(GqlSchema $schema, string $query): array
    {
        $gql = Craft::$app->getGql();
        $gql->flushCaches();
        return $gql->executeQuery($schema, $query);
    }
}
