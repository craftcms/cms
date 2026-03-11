<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestTracksChangesElement extends Element
{
    #[Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }

    #[Override]
    public static function find(): ElementQuery
    {
        return new ElementQuery(static::class);
    }
}

class TestTracksChangesEnabledElement extends TestTracksChangesElement
{
    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }
}

describe('Dirty Attributes', function () {
    test('starts clean', function () {
        $element = new TestTracksChangesElement;
        expect($element->getDirtyAttributes())->toBeEmpty()
            ->and($element->isAttributeDirty('title'))->toBeFalse();
    });

    test('can set dirty attributes', function () {
        $element = new TestTracksChangesElement;
        $element->setDirtyAttributes(['title', 'slug']);

        expect($element->getDirtyAttributes())->toEqualCanonicalizing(['title', 'slug'])
            ->and($element->isAttributeDirty('title'))->toBeTrue()
            ->and($element->isAttributeDirty('slug'))->toBeTrue();
    });

    test('can mark as dirty', function () {
        $element = new TestTracksChangesElement;
        $element->markAsDirty();

        expect($element->isAttributeDirty('any'))->toBeTrue();
    });

    test('can mark as clean', function () {
        $element = new TestTracksChangesElement;
        $element->setDirtyAttributes(['title']);
        $element->markAsClean();

        expect($element->getDirtyAttributes())->toBeEmpty()
            ->and($element->isAttributeDirty('title'))->toBeFalse();
    });
});

describe('Freshness', function () {
    test('getIsFresh defaults to false', function () {
        // Need siteSettingsId to test "not fresh"
        $element = new TestTracksChangesElement;
        $element->siteSettingsId = 1;

        expect($element->getIsFresh())->toBeFalse();
    });

    test('setIsFresh updates status', function () {
        $element = new TestTracksChangesElement;
        $element->siteSettingsId = 1;
        $element->setIsFresh(true);

        expect($element->getIsFresh())->toBeTrue();
    });

    test('getIsFresh returns false if errors', function () {
        $element = new TestTracksChangesElement;
        $element->errors()->add('title', 'Bad title');
        $element->setIsFresh(true);

        expect($element->getIsFresh())->toBeFalse();
    });
});

describe('Change Tracking', function () {
    test('returns empty outdated attributes if tracking disabled', function () {
        $element = new TestTracksChangesElement;
        // make it a derivative so it passes the canonical check
        $element->setCanonicalId(999);

        expect($element->getOutdatedAttributes())->toBeEmpty()
            ->and($element->isAttributeOutdated('title'))->toBeFalse();
    });

    test('returns empty modified attributes if tracking disabled', function () {
        $element = new TestTracksChangesElement;
        // make it a derivative
        $element->setCanonicalId(999);

        expect($element->getModifiedAttributes())->toBeEmpty()
            ->and($element->isAttributeModified('title'))->toBeFalse();
    });

    test('returns empty outdated/modified attributes if canonical', function () {
        $element = new TestTracksChangesEnabledElement;
        // It is canonical by default

        expect($element->getIsCanonical())->toBeTrue()
            ->and($element->getOutdatedAttributes())->toBeEmpty()
            ->and($element->getModifiedAttributes())->toBeEmpty();
    });

    test('returns outdated attributes correctly', function () {
        $element = new TestTracksChangesEnabledElement;
        $element->id = 100;
        $element->siteId = 1;
        $element->dateCreated = now()->subDays(2);
        $element->setCanonicalId(99); // Make it a derivative

        DB::table(Table::ELEMENTS)->insert([
            'id' => 100,
            'type' => TestTracksChangesEnabledElement::class,
            'enabled' => true,
            'archived' => false,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        // Insert changed attribute for the element
        DB::table(Table::CHANGEDATTRIBUTES)->insert([
            'elementId' => 100,
            'siteId' => 1,
            'attribute' => 'title',
            'dateUpdated' => now()->subDay(),
            'userId' => 1,
            'propagated' => true,
        ]);

        expect($element->getOutdatedAttributes())->toContain('title')
            ->and($element->isAttributeOutdated('title'))->toBeTrue();
    });

    test('filters outdated attributes by dateLastMerged', function () {
        $element = new TestTracksChangesEnabledElement;
        $element->id = 200;
        $element->siteId = 1;
        $element->dateCreated = now()->subDays(10);
        $element->dateLastMerged = now(); // Merged just now
        $element->setCanonicalId(199);

        DB::table(Table::ELEMENTS)->insert([
            'id' => 200,
            'type' => TestTracksChangesEnabledElement::class,
            'enabled' => true,
            'archived' => false,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        // Changed yesterday
        DB::table(Table::CHANGEDATTRIBUTES)->insert([
            'elementId' => 200,
            'siteId' => 1,
            'attribute' => 'title',
            'dateUpdated' => now()->subDay(),
            'userId' => 1,
            'propagated' => true,
        ]);

        expect($element->getOutdatedAttributes())->toBeEmpty();
    });

    test('returns modified attributes correctly', function () {
        $element = new TestTracksChangesEnabledElement;
        $element->id = 300;
        $element->siteId = 1;
        $element->setCanonicalId(299);

        DB::table(Table::ELEMENTS)->insert([
            'id' => 300,
            'type' => TestTracksChangesEnabledElement::class,
            'enabled' => true,
            'archived' => false,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        DB::table(Table::CHANGEDATTRIBUTES)->insert([
            'elementId' => 300,
            'siteId' => 1,
            'attribute' => 'slug',
            'dateUpdated' => now(),
            'userId' => 1,
            'propagated' => false,
        ]);

        expect($element->getModifiedAttributes())->toContain('slug')
            ->and($element->isAttributeModified('slug'))->toBeTrue();
    });

    test('returns attribute status', function () {
        $element = new TestTracksChangesEnabledElement;
        $element->id = 400;
        $element->siteId = 1;
        $element->setCanonicalId(399);

        DB::table(Table::ELEMENTS)->insert([
            'id' => 400,
            'type' => TestTracksChangesEnabledElement::class,
            'enabled' => true,
            'archived' => false,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        DB::table(Table::CHANGEDATTRIBUTES)->insert([
            'elementId' => 400,
            'siteId' => 1,
            'attribute' => 'slug',
            'dateUpdated' => now(),
            'userId' => 1,
            'propagated' => false,
        ]);

        $status = $element->getAttributeStatus('slug');
        expect($status)->not->toBeNull()
            ->and($status[0])->toBe(AttributeStatus::Modified);

        // Test outdated status
        $element2 = new TestTracksChangesEnabledElement;
        $element2->id = 401;
        $element2->siteId = 1;
        $element2->dateCreated = now()->subDays(5);
        $element2->setCanonicalId(399);

        DB::table(Table::ELEMENTS)->insert([
            'id' => 401,
            'type' => TestTracksChangesEnabledElement::class,
            'enabled' => true,
            'archived' => false,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        DB::table(Table::CHANGEDATTRIBUTES)->insert([
            'elementId' => 401,
            'siteId' => 1,
            'attribute' => 'title',
            'dateUpdated' => now()->subDay(),
            'userId' => 1,
            'propagated' => true,
        ]);

        $status2 = $element2->getAttributeStatus('title');
        expect($status2)->not->toBeNull()
            ->and($status2[0])->toBe(AttributeStatus::Modified);
    });
});
