<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User as UserElement;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->importer = ElementImporter::create()
        ->className(UserElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->userData = fn (array $addresses, array $overrides = []) => array_merge([
        'username' => 'importeduser',
        'email' => 'imported@example.com',
    ], $overrides, ['addresses' => $addresses]);

    $this->address = [
        'title' => 'address 1',
        'countryCode' => 'US',
        'addressLine1' => '123 Main St',
        'addressLine2' => 'Apt 4',
        'administrativeArea' => 'UT',
        'postalCode' => '12345',
        'locality' => 'My Town',
    ];
});

it('imports a user with an address', function () {
    $this->import->importItem($this->importer, ($this->userData)([$this->address]));

    $user = UserElement::find()->username('importeduser')->one();

    expect($user)->not()->toBeNull();
    expect(Address::find()->ownerId($user->id)->count())->toBe(1);
});

it('imports multiple addresses into the user', function () {
    $second = array_merge($this->address, ['title' => 'address 2', 'addressLine1' => '456 Elm St']);

    $this->import->importItem($this->importer, ($this->userData)([$this->address, $second]));

    $user = UserElement::find()->username('importeduser')->one();

    expect(Address::find()->ownerId($user->id)->count())->toBe(2);
});

it('maps native address fields correctly', function () {
    $this->import->importItem($this->importer, ($this->userData)([$this->address]));

    $user = UserElement::find()->username('importeduser')->one();
    $address = Address::find()->ownerId($user->id)->one();

    expect($address->countryCode)->toBe($this->address['countryCode'])
        ->and($address->addressLine1)->toBe($this->address['addressLine1'])
        ->and($address->addressLine2)->toBe($this->address['addressLine2'])
        ->and($address->administrativeArea)->toBe($this->address['administrativeArea'])
        ->and($address->postalCode)->toBe($this->address['postalCode'])
        ->and($address->locality)->toBe($this->address['locality'])
        ->and($address->title)->toBe($this->address['title']);
});

it('updates an existing address when match criteria matches', function () {
    // matchCriteria values are matched literally against the address's own saved attributes
    // (see Addresses::normalizeValueForImport(), which passes them straight into Typecast::configure()),
    // so the criteria value here must equal the address's actual title, not just reference the key name.
    $importer = (clone $this->importer)->matchCriteria(['email' => 'email']);

    $addressWithCriteria = array_merge($this->address, [
        'matchCriteria' => ['title' => $this->address['title']],
    ]);

    $this->import->importItem($importer, ($this->userData)([$addressWithCriteria]));

    $user = UserElement::find()->username('importeduser')->one();
    expect(Address::find()->ownerId($user->id)->count())->toBe(1);
    $originalAddressId = Address::find()->ownerId($user->id)->one()->id;

    $addressLine1Updated = '999 Updated Ave';

    $updated = array_merge($addressWithCriteria, ['addressLine1' => $addressLine1Updated]);
    $this->import->importItem($importer, ($this->userData)([$updated]));

    expect(Address::find()->ownerId($user->id)->count())->toBe(1);
    $address = Address::find()->ownerId($user->id)->one();
    expect($address->id)->toBe($originalAddressId);
    expect($address->addressLine1)->toBe($addressLine1Updated);
});

it('creates a new address when match criteria does not match any existing address', function () {
    $importer = (clone $this->importer)->matchCriteria(['email' => 'email']);

    $firstAddress = array_merge($this->address, [
        'matchCriteria' => ['title' => $this->address['title']],
    ]);

    $this->import->importItem($importer, ($this->userData)([$firstAddress]));

    $user = UserElement::find()->username('importeduser')->one();
    expect(Address::find()->ownerId($user->id)->count())->toBe(1);

    // re-importing the same address (matched by title) alongside a second, unmatched one
    // should reuse the first and create only one new address
    $newAddress = array_merge($this->address, [
        'title' => 'address 2',
        'addressLine1' => '456 Elm St',
        'matchCriteria' => ['title' => 'address 2'],
    ]);
    $this->import->importItem($importer, ($this->userData)([$firstAddress, $newAddress]));

    expect(Address::find()->ownerId($user->id)->count())->toBe(2);
});

it('skips empty or invalid address rows', function () {
    $emptyAddress = ['title' => '', 'addressLine1' => ''];

    $this->import->importItem($this->importer, ($this->userData)([$this->address, $emptyAddress, null, 'not-an-array']));

    $user = UserElement::find()->username('importeduser')->one();

    expect($user)->not()->toBeNull();
    expect(Address::find()->ownerId($user->id)->count())->toBe(1);
});

it('does not create any addresses when the addresses key is absent', function () {
    $data = ['username' => 'importeduser', 'email' => 'imported@example.com'];

    $this->import->importItem($this->importer, $data);

    $user = UserElement::find()->username('importeduser')->one();

    expect($user)->not()->toBeNull();
    expect(Address::find()->ownerId($user->id)->count())->toBe(0);
});
