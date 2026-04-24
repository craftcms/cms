<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

class TestHasCanonicalElement extends Element
{
    public $testAttr;

    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }
}

describe('Canonical ID and UID', function () {
    test('getId returns id', function () {
        $element = new TestHasCanonicalElement;
        $element->id = 123;
        expect($element->getId())->toBe(123);
    });

    test('getCanonicalId returns id if no canonical id set', function () {
        $element = new TestHasCanonicalElement;
        $element->id = 123;
        expect($element->getCanonicalId())->toBe(123);
    });

    test('getCanonicalId returns canonical id if set', function () {
        $element = new TestHasCanonicalElement;
        $element->id = 123;
        $element->setCanonicalId(456);
        expect($element->getCanonicalId())->toBe(456);
    });

    test('setCanonicalId updates canonical id', function () {
        $element = new TestHasCanonicalElement;
        $element->id = 123;
        $element->setCanonicalId(456);
        expect($element->getCanonicalId())->toBe(456);

        $element->setCanonicalId(123);
        expect($element->getCanonicalId())->toBe(123);
    });

    test('getCanonicalUid returns query result if not set', function () {
        // Setup a real entry to test the query
        actingAs(User::findOne());
        $entryModel = EntryModel::factory()->create();

        // Fetch the Element version (which has the HasCanonical trait)
        $canonical = Entry::find()->id($entryModel->id)->one();
        expect($canonical)->toBeInstanceOf(Entry::class);

        // Create a derivative entry manually
        $derivative = new Entry;
        $derivative->id = 99999;
        $derivative->setCanonicalId($canonical->id);

        // This should trigger the query to find the canonical's UID
        expect($derivative->getCanonicalUid())->toBe($canonical->uid);
    });
});

describe('Is Canonical/Derivative', function () {
    test('is canonical by default', function () {
        $element = new TestHasCanonicalElement;
        expect($element->getIsCanonical())->toBeTrue();
        expect($element->getIsDerivative())->toBeFalse();
    });

    test('is derivative if canonical id differs from id', function () {
        $element = new TestHasCanonicalElement;
        $element->id = 123;
        $element->setCanonicalId(456);
        expect($element->getIsCanonical())->toBeFalse();
        expect($element->getIsDerivative())->toBeTrue();
    });
});

describe('getCanonical', function () {
    test('returns self if canonical', function () {
        $element = new TestHasCanonicalElement;
        expect($element->getCanonical())->toBe($element);
    });

    test('fetches canonical from database if not set', function () {
        actingAs(User::findOne());
        $canonicalModel = EntryModel::factory()->create();

        // Fetch the Element version (which has the HasCanonical trait)
        $canonical = Entry::find()->id($canonicalModel->id)->one();
        expect($canonical)->toBeInstanceOf(Entry::class);

        // Create a derivative manually (simulating one)
        $derivative = new Entry;
        $derivative->id = 99999;
        $derivative->setCanonicalId($canonical->id);
        $derivative->siteId = $canonical->siteId;

        // This should execute the query
        $fetchedCanonical = $derivative->getCanonical();

        expect($fetchedCanonical)->toBeInstanceOf(Entry::class);
        expect($fetchedCanonical->id)->toBe($canonical->id);
    });
});

describe('setCanonical', function () {
    test('throws exception if element is canonical', function () {
        $element = new TestHasCanonicalElement;
        $other = new TestHasCanonicalElement;
        expect(fn () => $element->setCanonical($other))->toThrow(NotSupportedException::class);
    });

    test('sets canonical if element is derivative', function () {
        $element = new TestHasCanonicalElement;
        $element->id = 123;
        $element->setCanonicalId(456);

        $other = new TestHasCanonicalElement;
        $other->id = 456;

        $element->setCanonical($other);
        expect($element->getCanonical())->toBe($other);
    });
});

describe('mergeCanonicalChanges', function () {
    test('merges changes from canonical', function () {
        // Create canonical element
        $canonical = new TestHasCanonicalElement;
        $canonical->id = 100;
        $canonical->testAttr = 'Canonical Value';

        // Create a derivative with a mock for getOutdatedAttributes and isAttributeModified
        $derivative = Mockery::mock(TestHasCanonicalElement::class)->makePartial();
        $derivative->id = 200;
        $derivative->setCanonicalId($canonical->id);
        $derivative->testAttr = 'Old Value';

        // Mock getOutdatedAttributes to return our test attribute
        $derivative->shouldReceive('getOutdatedAttributes')
            ->andReturn(['testAttr']);

        // Mock isAttributeModified to return false (not modified)
        $derivative->shouldReceive('isAttributeModified')
            ->with('testAttr')
            ->andReturn(false);

        // Mock getOutdatedFields to return empty array
        $derivative->shouldReceive('getOutdatedFields')
            ->andReturn([]);

        // Mock getCanonical to return our canonical element
        $derivative->shouldReceive('getCanonical')
            ->andReturn($canonical);

        // Run merge
        $derivative->mergeCanonicalChanges();

        expect($derivative->testAttr)->toBe('Canonical Value');
    });

    test('does not overwrite modified attributes', function () {
        // Create canonical element
        $canonical = new TestHasCanonicalElement;
        $canonical->id = 100;
        $canonical->testAttr = 'Canonical Value';

        // Create a derivative
        $derivative = Mockery::mock(TestHasCanonicalElement::class)->makePartial();
        $derivative->id = 200;
        $derivative->setCanonicalId($canonical->id);
        $derivative->testAttr = 'My Custom Value';

        // Mock getOutdatedAttributes to return our test attribute
        $derivative->shouldReceive('getOutdatedAttributes')
            ->andReturn(['testAttr']);

        // Mock isAttributeModified to return true (modified)
        $derivative->shouldReceive('isAttributeModified')
            ->with('testAttr')
            ->andReturn(true);

        // Mock getOutdatedFields to return empty array
        $derivative->shouldReceive('getOutdatedFields')
            ->andReturn([]);

        // Mock getCanonical to return our canonical element
        $derivative->shouldReceive('getCanonical')
            ->andReturn($canonical);

        // Run merge
        $derivative->mergeCanonicalChanges();

        // Should NOT be overwritten
        expect($derivative->testAttr)->toBe('My Custom Value');
    });
});
