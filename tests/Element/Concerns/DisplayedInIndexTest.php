<?php

declare(strict_types=1);

use craft\events\RegisterElementDefaultCardAttributesEvent;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementTableAttributesEvent;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use yii\base\Event;

/**
 * Test Entry class that exposes protected methods from DisplayedInIndex trait
 */
class TestEntryForDisplayedInIndex extends Entry
{
    public static function exposeDefineSortOptions(): array
    {
        return self::defineSortOptions();
    }

    public static function exposeDefineTableAttributes(): array
    {
        return self::defineTableAttributes();
    }

    public static function exposeDefineDefaultTableAttributes(string $source): array
    {
        return self::defineDefaultTableAttributes($source);
    }

    public static function exposeDefineCardAttributes(): array
    {
        return self::defineCardAttributes();
    }

    public static function exposeDefineDefaultCardAttributes(): array
    {
        return self::defineDefaultCardAttributes();
    }

    public static function exposePrepElementQueryForTableAttribute(
        ElementQueryInterface $elementQuery,
        string $attribute,
    ): void {
        self::prepElementQueryForTableAttribute($elementQuery, $attribute);
    }

    public static function exposeIndexElements(
        ElementQueryInterface $elementQuery,
        ?string $sourceKey,
    ): array {
        return self::indexElements($elementQuery, $sourceKey);
    }
}

/**
 * Test Entry class without URIs to test conditional behavior
 */
class TestEntryWithoutUris extends Entry
{
    #[\Override]
    public static function hasUris(): bool
    {
        return false;
    }
}

/**
 * Test Entry class without statuses to test conditional behavior
 */
class TestEntryWithoutStatuses extends Entry
{
    #[\Override]
    public static function hasStatuses(): bool
    {
        return false;
    }
}

describe('tableAttributes', function () {
    test('returns default table attributes', function () {
        $attributes = Entry::tableAttributes();
        expect($attributes)->toBeArray();
        expect($attributes)->toHaveKey('dateCreated');
        expect($attributes)->toHaveKey('dateUpdated');
        expect($attributes)->toHaveKey('id');
        expect($attributes)->toHaveKey('uid');
    });

    test('includes URI-related attributes when hasUris returns true', function () {
        $attributes = Entry::tableAttributes();
        expect($attributes)->toHaveKey('link');
        expect($attributes)->toHaveKey('slug');
        expect($attributes)->toHaveKey('uri');
        expect($attributes['link'])->toHaveKey('icon');
    });

    test('excludes URI-related attributes when hasUris returns false', function () {
        $attributes = TestEntryWithoutUris::tableAttributes();
        expect($attributes)->not->toHaveKey('link');
        expect($attributes)->not->toHaveKey('slug');
        expect($attributes)->not->toHaveKey('uri');
    });

    test('includes status attribute when hasStatuses returns true', function () {
        $attributes = Entry::tableAttributes();
        expect($attributes)->toHaveKey('status');
    });

    test('excludes status attribute when hasStatuses returns false', function () {
        $attributes = TestEntryWithoutStatuses::tableAttributes();
        expect($attributes)->not->toHaveKey('status');
    });
});

describe('defineTableAttributes', function () {
    test('returns base attributes from parent', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineTableAttributes();
        expect($attributes)->toBeArray();
        expect($attributes)->toHaveKey('dateCreated');
        expect($attributes)->toHaveKey('dateUpdated');
        expect($attributes)->toHaveKey('id');
        expect($attributes)->toHaveKey('uid');
        expect($attributes)->toHaveKey('status');
        expect($attributes)->toHaveKey('link');
        expect($attributes)->toHaveKey('slug');
        expect($attributes)->toHaveKey('uri');
    });

    test('includes entry-specific attributes', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineTableAttributes();
        expect($attributes)->toHaveKey('section');
        expect($attributes)->toHaveKey('type');
        expect($attributes)->toHaveKey('authors');
        expect($attributes)->toHaveKey('ancestors');
        expect($attributes)->toHaveKey('parent');
        expect($attributes)->toHaveKey('postDate');
        expect($attributes)->toHaveKey('expiryDate');
        expect($attributes)->toHaveKey('revisionNotes');
        expect($attributes)->toHaveKey('revisionCreator');
        expect($attributes)->toHaveKey('drafts');
    });
});

describe('defaultTableAttributes', function () {
    test('returns default attributes for all entries source', function () {
        $attributes = Entry::defaultTableAttributes('*');
        expect($attributes)->toBeArray();
        expect($attributes)->toContain('status');
        expect($attributes)->toContain('section');
        expect($attributes)->toContain('postDate');
        expect($attributes)->toContain('expiryDate');
        expect($attributes)->toContain('authors');
        expect($attributes)->toContain('link');
    });

    test('returns default attributes for singles source', function () {
        $attributes = Entry::defaultTableAttributes('singles');
        expect($attributes)->toBeArray();
        expect($attributes)->toContain('status');
        expect($attributes)->not->toContain('postDate');
        expect($attributes)->not->toContain('expiryDate');
        expect($attributes)->not->toContain('authors');
        expect($attributes)->toContain('link');
    });
});

describe('defineDefaultTableAttributes', function () {
    test('includes section attribute for all entries source', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineDefaultTableAttributes('*');
        expect($attributes)->toContain('section');
    });

    test('excludes section attribute for singles source', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineDefaultTableAttributes('singles');
        expect($attributes)->not->toContain('section');
    });

    test('excludes date and author attributes for singles source', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineDefaultTableAttributes('singles');
        expect($attributes)->not->toContain('postDate');
        expect($attributes)->not->toContain('expiryDate');
        expect($attributes)->not->toContain('authors');
    });

    test('includes date and author attributes for non-singles sources', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineDefaultTableAttributes('section:blog');
        expect($attributes)->toContain('postDate');
        expect($attributes)->toContain('expiryDate');
        expect($attributes)->toContain('authors');
    });
});

describe('sortOptions', function () {
    test('returns sort options with ID first', function () {
        $options = Entry::sortOptions();
        expect($options)->toBeArray();
        expect(array_key_first($options))->toBe('id');
    });

    test('includes basic sort options', function () {
        $options = Entry::sortOptions();
        expect($options)->toHaveKey('title');
        expect($options)->toHaveKey('slug');
        expect($options)->toHaveKey('uri');
    });

    test('includes complex sort options with orderBy callbacks', function () {
        $options = Entry::sortOptions();
        // Complex options are indexed arrays - some have 'attribute' key, some don't
        $complexOptions = array_filter($options, is_array(...));
        $attributes = array_column($complexOptions, 'attribute');

        expect($attributes)->toContain('section');
        expect($attributes)->toContain('type');
        expect($attributes)->toContain('postDate');
        // expiryDate doesn't have an 'attribute' key, it's just orderBy => 'expiryDate'
    });

    test('complex sort options have proper structure', function () {
        $options = Entry::sortOptions();
        $complexOptions = array_filter($options, is_array(...));

        expect($complexOptions)->not->toBeEmpty();

        $firstComplex = array_values($complexOptions)[0];
        expect($firstComplex)->toHaveKey('label');
        expect($firstComplex)->toHaveKey('orderBy');
    });
});

describe('defineSortOptions', function () {
    test('returns entry-specific sort options structure', function () {
        $options = TestEntryForDisplayedInIndex::exposeDefineSortOptions();
        expect($options)->toBeArray();
        expect($options)->toHaveKey('title');
        expect($options)->toHaveKey('slug');
        expect($options)->toHaveKey('uri');
    });

    test('includes complex sort options', function () {
        $options = TestEntryForDisplayedInIndex::exposeDefineSortOptions();
        $complexOptions = array_filter($options, is_array(...));
        $attributes = array_column($complexOptions, 'attribute');

        expect($attributes)->toContain('section');
        expect($attributes)->toContain('type');
        expect($attributes)->toContain('postDate');
        // expiryDate doesn't have an 'attribute' key
    });

    test('section sort option has callable orderBy', function () {
        $options = TestEntryForDisplayedInIndex::exposeDefineSortOptions();
        $sectionOption = array_values(array_filter($options, fn ($opt) => is_array($opt) && ($opt['attribute'] ?? null) === 'section'))[0] ?? null;

        expect($sectionOption)->not->toBeNull();
        expect($sectionOption['orderBy'])->toBeCallable();
    });

    test('entry type sort option has callable orderBy with database connection parameter', function () {
        $options = TestEntryForDisplayedInIndex::exposeDefineSortOptions();
        $typeOption = array_values(array_filter($options, fn ($opt) => is_array($opt) && ($opt['attribute'] ?? null) === 'type'))[0] ?? null;

        expect($typeOption)->not->toBeNull();
        expect($typeOption['orderBy'])->toBeCallable();
    });
});

describe('indexViewModes', function () {
    test('returns available view modes', function () {
        $viewModes = Entry::indexViewModes();
        expect($viewModes)->toBeArray();

        // Entry has structure, table, and cards (no thumbs since hasThumbs() returns false)
        expect($viewModes)->toHaveCount(3);

        $modes = array_column($viewModes, 'mode');
        expect($modes)->toContain('structure');
        expect($modes)->toContain('table');
        expect($modes)->toContain('cards');
    });
});

describe('cardAttributes', function () {
    test('returns card attributes', function () {
        $attributes = Entry::cardAttributes();
        expect($attributes)->toBeArray();
        expect($attributes)->toHaveKey('dateCreated');
        expect($attributes)->toHaveKey('dateUpdated');
        expect($attributes)->toHaveKey('id');
        expect($attributes)->toHaveKey('uid');
    });

    test('includes URI-related card attributes when hasUris returns true', function () {
        $attributes = Entry::cardAttributes();
        expect($attributes)->toHaveKey('link');
        expect($attributes)->toHaveKey('slug');
        expect($attributes)->toHaveKey('uri');
        expect($attributes['link'])->toHaveKey('placeholder');
        expect($attributes['link']['placeholder'])->toBeCallable();
    });

    test('excludes URI-related card attributes when hasUris returns false', function () {
        $attributes = TestEntryWithoutUris::cardAttributes();
        expect($attributes)->not->toHaveKey('link');
        expect($attributes)->not->toHaveKey('slug');
        expect($attributes)->not->toHaveKey('uri');
    });

    test('card attributes have placeholder callbacks', function () {
        $attributes = Entry::cardAttributes();
        expect($attributes['dateCreated'])->toHaveKey('placeholder');
        expect($attributes['dateCreated']['placeholder'])->toBeCallable();
        expect($attributes['id'])->toHaveKey('placeholder');
        expect($attributes['id']['placeholder'])->toBeCallable();
    });
});

describe('defineCardAttributes', function () {
    test('returns entry-specific card attributes', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineCardAttributes();
        expect($attributes)->toBeArray();
        expect($attributes)->toHaveKey('section');
        expect($attributes)->toHaveKey('type');
        expect($attributes)->toHaveKey('authors');
        expect($attributes)->toHaveKey('parent');
        expect($attributes)->toHaveKey('postDate');
        expect($attributes)->toHaveKey('expiryDate');
        expect($attributes)->toHaveKey('revisionNotes');
        expect($attributes)->toHaveKey('revisionCreator');
        expect($attributes)->toHaveKey('drafts');
    });

    test('entry card attributes have placeholder callbacks', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineCardAttributes();
        expect($attributes['section']['placeholder'])->toBeCallable();
        expect($attributes['type']['placeholder'])->toBeCallable();
        expect($attributes['authors']['placeholder'])->toBeCallable();
        expect($attributes['parent']['placeholder'])->toBeCallable();
        expect($attributes['postDate']['placeholder'])->toBeCallable();
        expect($attributes['expiryDate']['placeholder'])->toBeCallable();
    });

    test('parent attribute placeholder returns HTML string', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineCardAttributes();
        $placeholder = $attributes['parent']['placeholder'];
        $result = $placeholder();
        expect($result)->toBeString();
        expect($result)->toContain('card-placeholder');
    });
});

describe('defaultCardAttributes', function () {
    test('returns default card attributes', function () {
        $attributes = Entry::defaultCardAttributes();
        expect($attributes)->toBeArray();
    });
});

describe('defineDefaultCardAttributes', function () {
    test('returns empty array by default', function () {
        $attributes = TestEntryForDisplayedInIndex::exposeDefineDefaultCardAttributes();
        expect($attributes)->toBeArray();
        expect($attributes)->toBeEmpty();
    });
});

describe('baseBulkDuplicateAttributes', function () {
    test('returns attributes to exclude from duplication', function () {
        $attributes = Entry::baseBulkDuplicateAttributes();
        expect($attributes)->toBeArray();
        expect($attributes)->toHaveKey('structureId');
        expect($attributes)->toHaveKey('root');
        expect($attributes)->toHaveKey('lft');
        expect($attributes)->toHaveKey('rgt');
        expect($attributes)->toHaveKey('level');
    });

    test('includes entry-specific duplicate attributes', function () {
        $attributes = Entry::baseBulkDuplicateAttributes();
        expect($attributes)->toHaveKey('sectionId');
        expect($attributes['sectionId'])->toBeNull();
    });
});

describe('attributePreviewHtml', function () {
    test('returns preview HTML for attribute', function () {
        $attribute = [
            'value' => 'dateCreated',
            'label' => 'Date Created',
            'placeholder' => fn () => new DateTime,
        ];

        $html = Entry::attributePreviewHtml($attribute);
        expect($html)->toBeString();
    });

    test('returns placeholder for link attribute', function () {
        $attribute = [
            'value' => 'link',
            'placeholder' => '<a href="#">Link</a>',
        ];

        $html = Entry::attributePreviewHtml($attribute);
        expect($html)->toBe('<a href="#">Link</a>');
    });

    test('returns placeholder for authors attribute', function () {
        $attribute = [
            'value' => 'authors',
            'placeholder' => '<span>Author</span>',
        ];

        $html = Entry::attributePreviewHtml($attribute);
        expect($html)->toBe('<span>Author</span>');
    });

    test('returns placeholder for parent attribute', function () {
        $attribute = [
            'value' => 'parent',
            'placeholder' => '<span>Parent</span>',
        ];

        $html = Entry::attributePreviewHtml($attribute);
        expect($html)->toBe('<span>Parent</span>');
    });
});

describe('defineSearchableAttributes', function () {
    test('can be overridden in subclasses', function () {
        // Entry should have its own searchable attributes
        $attributes = Entry::searchableAttributes();
        expect($attributes)->toBeArray();
    });
});

describe('indexElementCount', function () {
    test('returns zero for empty query', function () {
        $query = entryQuery();
        $count = Entry::indexElementCount($query, null);
        expect($count)->toBeInt();
        expect($count)->toBe(0);
    });

    test('returns correct count for entries query', function () {
        $entries = EntryModel::factory()->count(5)->create();
        expect($entries)->toHaveCount(5);

        $query = entryQuery();
        $count = Entry::indexElementCount($query, null);
        expect($count)->toBe(5);
    });

    test('returns correct count with filtered query', function () {
        $entry1 = EntryModel::factory()->create();
        $entry2 = EntryModel::factory()->create();
        $entry3 = EntryModel::factory()->create();

        $query = entryQuery()->id($entry1->id);
        $count = Entry::indexElementCount($query, null);
        expect($count)->toBe(1);
    });

    test('accepts source key parameter', function () {
        $query = entryQuery();
        $count = Entry::indexElementCount($query, '*');
        expect($count)->toBeInt();
    });
});

describe('prepElementQueryForTableAttribute', function () {
    test('method can be called for ancestors attribute', function () {
        $query = entryQuery();
        // This should not throw an exception
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'ancestors');
        expect(true)->toBeTrue();
    });

    test('method can be called for parent attribute', function () {
        $query = entryQuery();
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'parent');
        expect(true)->toBeTrue();
    });

    test('method can be called for revisionNotes attribute', function () {
        $query = entryQuery();
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'revisionNotes');
        expect(true)->toBeTrue();
    });

    test('method can be called for revisionCreator attribute', function () {
        $query = entryQuery();
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'revisionCreator');
        expect(true)->toBeTrue();
    });

    test('method can be called for drafts attribute', function () {
        $query = entryQuery();
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'drafts');
        expect(true)->toBeTrue();
    });

    test('method can be called for authors attribute (Entry override)', function () {
        $query = entryQuery();
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'authors');
        expect(true)->toBeTrue();
    });

    test('does nothing for unknown attributes', function () {
        $query = entryQuery();
        // This should not throw an exception
        TestEntryForDisplayedInIndex::exposePrepElementQueryForTableAttribute($query, 'unknownAttribute');
        expect(true)->toBeTrue();
    });
});

describe('indexElements', function () {
    test('returns empty array for empty query', function () {
        $query = entryQuery();
        $elements = TestEntryForDisplayedInIndex::exposeIndexElements($query, null);
        expect($elements)->toBeArray();
        expect($elements)->toBeEmpty();
    });

    test('returns elements from query', function () {
        EntryModel::factory()->count(3)->create();

        $query = entryQuery();
        $elements = TestEntryForDisplayedInIndex::exposeIndexElements($query, null);
        expect($elements)->toBeArray();
        expect($elements)->toHaveCount(3);
        expect($elements[0])->toBeInstanceOf(Entry::class);
    });

    test('respects query limits', function () {
        EntryModel::factory()->count(5)->create();

        $query = entryQuery()->limit(2);
        $elements = TestEntryForDisplayedInIndex::exposeIndexElements($query, null);
        expect($elements)->toHaveCount(2);
    });

    test('accepts source key parameter', function () {
        EntryModel::factory()->count(2)->create();

        $query = entryQuery();
        $elements = TestEntryForDisplayedInIndex::exposeIndexElements($query, '*');
        expect($elements)->toBeArray();
        expect($elements)->toHaveCount(2);
    });
});

describe('events', function () {
    test('registerTableAttributes event is triggered', function () {
        $eventTriggered = false;
        $capturedAttributes = null;

        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function (RegisterElementTableAttributesEvent $event) use (&$eventTriggered, &$capturedAttributes) {
                $eventTriggered = true;
                $capturedAttributes = $event->tableAttributes;
            }
        );

        $attributes = Entry::tableAttributes();

        expect($eventTriggered)->toBeTrue();
        expect($capturedAttributes)->toBeArray();
        expect($capturedAttributes)->toEqual($attributes);

        // Clean up
        Event::off(Entry::class, Element::EVENT_REGISTER_TABLE_ATTRIBUTES);
    });

    test('registerSortOptions event is triggered', function () {
        $eventTriggered = false;

        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_SORT_OPTIONS,
            function (RegisterElementSortOptionsEvent $event) use (&$eventTriggered) {
                $eventTriggered = true;
            }
        );

        Entry::sortOptions();

        expect($eventTriggered)->toBeTrue();

        // Clean up
        Event::off(Entry::class, Element::EVENT_REGISTER_SORT_OPTIONS);
    });

    test('registerDefaultTableAttributes event is triggered', function () {
        $eventTriggered = false;
        $capturedSource = null;

        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES,
            function (RegisterElementDefaultTableAttributesEvent $event) use (&$eventTriggered, &$capturedSource) {
                $eventTriggered = true;
                $capturedSource = $event->source;
            }
        );

        Entry::defaultTableAttributes('*');

        expect($eventTriggered)->toBeTrue();
        expect($capturedSource)->toBe('*');

        // Clean up
        Event::off(Entry::class, Element::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES);
    });

    test('registerDefaultCardAttributes event is triggered', function () {
        $eventTriggered = false;

        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES,
            function (RegisterElementDefaultCardAttributesEvent $event) use (&$eventTriggered) {
                $eventTriggered = true;
            }
        );

        Entry::defaultCardAttributes();

        expect($eventTriggered)->toBeTrue();

        // Clean up
        Event::off(Entry::class, Element::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES);
    });
});
