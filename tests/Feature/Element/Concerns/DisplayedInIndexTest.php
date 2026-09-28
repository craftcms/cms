<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementDefaultCardAttributesResolving;
use CraftCms\Cms\Element\Events\ElementDefaultTableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementSearchableAttributesResolving;
use CraftCms\Cms\Element\Events\ElementSortOptionsResolving;
use CraftCms\Cms\Element\Events\ElementTableAttributesResolving;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

/**
 * Test Entry class without URIs to test conditional behavior
 */
class TestEntryWithoutUris extends Entry
{
    #[Override]
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
    #[Override]
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

    test('includes entry-specific attributes', function () {
        $attributes = Entry::tableAttributes();
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
        expect($attributes)->not->toContain('section');
        expect($attributes)->not->toContain('postDate');
        expect($attributes)->not->toContain('expiryDate');
        expect($attributes)->not->toContain('authors');
        expect($attributes)->toContain('link');
    });

    test('returns default attributes for a section source', function () {
        $attributes = Entry::defaultTableAttributes('section:blog');
        expect($attributes)->toBe(['status', 'postDate', 'expiryDate', 'authors', 'link']);
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
    });

    test('complex sort options have proper structure', function () {
        $options = Entry::sortOptions();
        $complexOptions = array_filter($options, is_array(...));

        expect($complexOptions)->not->toBeEmpty();

        $firstComplex = Arr::first($complexOptions);
        expect($firstComplex)->toHaveKey('label');
        expect($firstComplex)->toHaveKey('orderBy');
    });

    test('section sort option has callable orderBy', function () {
        $options = Entry::sortOptions();
        $sectionOption = Arr::first(array_filter($options, fn ($opt) => is_array($opt) && ($opt['attribute'] ?? null) === 'section')) ?? null;

        expect($sectionOption)->not->toBeNull();
        expect($sectionOption['orderBy'])->toBeCallable();
    });

    test('entry type sort option has callable orderBy with database connection parameter', function () {
        $options = Entry::sortOptions();
        $typeOption = Arr::first(array_filter($options, fn ($opt) => is_array($opt) && ($opt['attribute'] ?? null) === 'type')) ?? null;

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

describe('entry cardAttributes', function () {
    test('returns entry-specific card attributes', function () {
        $attributes = Entry::cardAttributes();
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
        $attributes = Entry::cardAttributes();
        expect($attributes['section']['placeholder'])->toBeCallable();
        expect($attributes['type']['placeholder'])->toBeCallable();
        expect($attributes['authors']['placeholder'])->toBeCallable();
        expect($attributes['parent']['placeholder'])->toBeCallable();
        expect($attributes['postDate']['placeholder'])->toBeCallable();
        expect($attributes['expiryDate']['placeholder'])->toBeCallable();
    });

    test('parent attribute placeholder returns HTML string', function () {
        $attributes = Entry::cardAttributes();
        $placeholder = $attributes['parent']['placeholder'];
        $result = $placeholder();
        expect($result)->toBeString();
        expect($result)->toContain('card-placeholder');
    });
});

describe('defaultCardAttributes', function () {
    test('returns no default card attributes for entries', function () {
        expect(Entry::defaultCardAttributes())->toBe([]);
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

describe('searchableAttributes', function () {
    test('returns the attributes the element type defines', function () {
        expect(User::searchableAttributes())->toBe(['username', 'fullName', 'firstName', 'lastName', 'email']);
    });

    test('ElementSearchableAttributesResolving listeners can modify the attributes', function () {
        Event::listen(function (ElementSearchableAttributesResolving $event) {
            if ($event->elementType === User::class) {
                $event->attributes = [...array_diff($event->attributes, ['email']), 'affiliatedSite'];
            }
        });

        expect(User::searchableAttributes())->toBe(['username', 'fullName', 'firstName', 'lastName', 'affiliatedSite']);
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
});

describe('prepElementQueryForTableAttribute', function () {
    test('eager-loads what table columns need', function (string $attribute, ?array $expectedWith) {
        actingAs(User::findOne());

        $query = entryQuery();

        Entry::indexData($query, null, ['mode' => 'table', 'tableColumns' => [$attribute]], '*', 'index', false, false);

        expect($query->with)->toBe($expectedWith);
    })->with([
        'ancestors' => ['ancestors', [['ancestors', ['status' => null]]]],
        'parent' => ['parent', [['parent', ['status' => null]]]],
        'revisionNotes' => ['revisionNotes', ['currentRevision']],
        'revisionCreator' => ['revisionCreator', ['currentRevision.revisionCreator']],
        'drafts' => ['drafts', [['drafts', ['status' => null, 'orderBy' => ['dateUpdated' => SORT_DESC]]]]],
        'authors' => ['authors', [['authors', ['status' => null]]]],
        'attribute without relations' => ['id', null],
    ]);
});

describe('indexElements', function () {
    test('returns empty array for empty query', function () {
        $query = entryQuery();
        $elements = Entry::indexElements($query, null);
        expect($elements)->toBeArray();
        expect($elements)->toBeEmpty();
    });

    test('returns elements from query', function () {
        EntryModel::factory()->count(3)->create();

        $query = entryQuery();
        $elements = Entry::indexElements($query, null);
        expect($elements)->toBeArray();
        expect($elements)->toHaveCount(3);
        expect($elements[0])->toBeInstanceOf(Entry::class);
    });

    test('respects query limits', function () {
        EntryModel::factory()->count(5)->create();

        $query = entryQuery()->limit(2);
        $elements = Entry::indexElements($query, null);
        expect($elements)->toHaveCount(2);
    });

    test('accepts source key parameter', function () {
        EntryModel::factory()->count(2)->create();

        $query = entryQuery();
        $elements = Entry::indexElements($query, '*');
        expect($elements)->toBeArray();
        expect($elements)->toHaveCount(2);
    });

    test('removes exclude descendant ids expressions from subquery wheres', function () {
        $query = entryQuery();
        $excludeDescendantIdsExpression = new ExcludeDescendantIdsExpression([10, 11]);

        $query
            ->where($excludeDescendantIdsExpression)
            ->where('elements.id', 1);

        $method = new ReflectionMethod(Entry::class, 'elementQueryWithAllDescendants');

        $result = $method->invoke(null, $query);

        expect($result)->not->toBe($query);
        expect($result->getQuery()->wheres)->not()->toContain($excludeDescendantIdsExpression);
    });
});

describe('events', function () {
    test('registerTableAttributes event is triggered', function () {
        $eventTriggered = false;
        $capturedAttributes = null;

        Event::listen(function (ElementTableAttributesResolving $event) use (&$eventTriggered, &$capturedAttributes) {
            if ($event->elementType !== Entry::class) {
                return;
            }
            $eventTriggered = true;
            $capturedAttributes = $event->tableAttributes;
        });

        $attributes = Entry::tableAttributes();

        expect($eventTriggered)->toBeTrue();
        expect($capturedAttributes)->toBeArray();
        expect($capturedAttributes)->toEqual($attributes);
    });

    test('registerSortOptions event is triggered', function () {
        $eventTriggered = false;

        Event::listen(function (ElementSortOptionsResolving $event) use (&$eventTriggered) {
            if ($event->elementType !== Entry::class) {
                return;
            }
            $eventTriggered = true;
        });

        Entry::sortOptions();

        expect($eventTriggered)->toBeTrue();
    });

    test('registerDefaultTableAttributes event is triggered', function () {
        $eventTriggered = false;
        $capturedSource = null;

        Event::listen(function (ElementDefaultTableAttributesResolving $event) use (&$eventTriggered, &$capturedSource) {
            if ($event->elementType !== Entry::class) {
                return;
            }
            $eventTriggered = true;
            $capturedSource = $event->source;
        });

        Entry::defaultTableAttributes('*');

        expect($eventTriggered)->toBeTrue();
        expect($capturedSource)->toBe('*');
    });

    test('registerDefaultCardAttributes event is triggered', function () {
        $eventTriggered = false;

        Event::listen(function (ElementDefaultCardAttributesResolving $event) use (&$eventTriggered) {
            if ($event->elementType !== Entry::class) {
                return;
            }
            $eventTriggered = true;
        });

        Entry::defaultCardAttributes();

        expect($eventTriggered)->toBeTrue();
    });
});
