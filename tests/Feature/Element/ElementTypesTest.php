<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element as BaseElement;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User as UserModel;

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
    $missingId = ((int) ElementModel::withTrashed()->max('id')) + 1;

    expect($elementTypes->getElementTypeById($missingId))->toBeNull()
        ->and($elementTypes->getElementTypeByUid('missing-uid'))->toBeNull()
        ->and($elementTypes->getElementTypeByKey('id', $missingId))->toBeNull()
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

test('reflects element type registration changes in resolved ref handles', function () {
    $registry = app(ElementTypes::class);
    $registry->register(TestRegisteredElementType::class);

    $elementTypes = app(Elements::class);

    expect($elementTypes->getElementTypeByRefHandle('test-registered-element'))->toBe(TestRegisteredElementType::class);

    $registry->remove(TestRegisteredElementType::class);

    expect($elementTypes->getElementTypeByRefHandle('test-registered-element'))->toBeNull();
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
