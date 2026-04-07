<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Element as BaseElement;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\RegisterElementTypes;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;

test('returns element types by id uid and key', function () {
    $entry = EntryModel::factory()->createElement();

    $elementTypes = app(Elements::class);

    expect($elementTypes->getElementTypeById($entry->id))->toBe(EntryElement::class)
        ->and($elementTypes->getElementTypeByUid($entry->uid))->toBe(EntryElement::class)
        ->and($elementTypes->getElementTypeByKey('id', $entry->id))->toBe(EntryElement::class)
        ->and($elementTypes->getElementTypeByKey('uid', $entry->uid))->toBe(EntryElement::class);
});

test('returns null when an element type cannot be found', function () {
    $elementTypes = app(Elements::class);

    expect($elementTypes->getElementTypeById(9999))->toBeNull()
        ->and($elementTypes->getElementTypeByUid('missing-uid'))->toBeNull()
        ->and($elementTypes->getElementTypeByKey('id', 9999))->toBeNull()
        ->and($elementTypes->getElementTypeByKey('uid', 'missing-uid'))->toBeNull();
});

test('returns distinct element types for ids', function () {
    $firstEntry = EntryModel::factory()->createElement();
    $secondEntry = EntryModel::factory()->createElement();
    $user = UserModel::factory()->createElement();

    $types = (app(Elements::class))->getElementTypesByIds([
        $firstEntry->id,
        $secondEntry->id,
        $user->id,
    ]);

    expect($types)->toHaveCount(2)
        ->toEqualCanonicalizing([
            EntryElement::class,
            UserElement::class,
        ]);
});

test('returns all built-in element types and registered element types', function () {
    Event::listen(RegisterElementTypes::class, function (RegisterElementTypes $event) {
        $event->types[] = TestRegisteredElementType::class;
    });

    $types = (app(Elements::class))->getAllElementTypes();

    expect($types)->toHaveCount(5)
        ->toContain(
            Address::class,
            Asset::class,
            EntryElement::class,
            UserElement::class,
            TestRegisteredElementType::class,
        );
});

test('matches ref handles case-insensitively', function () {
    expect((app(Elements::class))->getElementTypeByRefHandle('UsEr'))->toBe(UserElement::class);
});

test('returns element subclasses passed as ref handles', function () {
    expect((app(Elements::class))->getElementTypeByRefHandle(TestRegisteredElementType::class))
        ->toBe(TestRegisteredElementType::class);
});

test('falls back to entries for removed legacy ref handles', function (string $refHandle) {
    expect((app(Elements::class))->getElementTypeByRefHandle($refHandle))->toBe(EntryElement::class);
})->with([
    'category' => 'category',
    'tag' => 'tag',
    'globalset' => 'globalset',
]);

test('returns null for unknown ref handles', function () {
    expect((app(Elements::class))->getElementTypeByRefHandle('missing-ref-handle'))->toBeNull();
});

test('caches resolved ref handles', function () {
    $listenerCalls = 0;

    Event::listen(RegisterElementTypes::class, function (RegisterElementTypes $event) use (&$listenerCalls) {
        $listenerCalls++;
        $event->types[] = TestRegisteredElementType::class;
    });

    $elementTypes = app(Elements::class);

    expect($elementTypes->getElementTypeByRefHandle('test-registered-element'))->toBe(TestRegisteredElementType::class);

    Event::forget(RegisterElementTypes::class);

    expect($elementTypes->getElementTypeByRefHandle('test-registered-element'))->toBe(TestRegisteredElementType::class)
        ->and($listenerCalls)->toBe(1);
});

class TestRegisteredElementType extends BaseElement
{
    #[Override]
    public static function displayName(): string
    {
        return 'Test Registered Element';
    }

    public static function refHandle(): ?string
    {
        return 'test-registered-element';
    }
}
