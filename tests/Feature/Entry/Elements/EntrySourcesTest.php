<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementSourcesResolving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

/**
 * Locates one of Entry's native sources by key.
 */
final class EntrySourceLookup
{
    /** @return array<string, mixed>|null */
    public static function find(string $key, string $context = 'index'): ?array
    {
        /** @var array<string, mixed>|null */
        return collect(Entry::sources($context))->firstWhere('key', $key);
    }
}

describe('single sections as sources', function () {
    test('emits one section source per single', function () {
        $home = Section::factory()->create([
            'type' => SectionType::Single,
            'name' => 'Homepage',
            'handle' => 'homePage',
        ]);
        $about = Section::factory()->create([
            'type' => SectionType::Single,
            'name' => 'About',
            'handle' => 'aboutPage',
        ]);

        expect(EntrySourceLookup::find("section:{$home->uid}"))->not->toBeNull()
            ->and(EntrySourceLookup::find("section:{$about->uid}"))->not->toBeNull();
    });

    test('no longer emits an aggregate singles source', function () {
        Section::factory()->create(['type' => SectionType::Single]);

        expect(EntrySourceLookup::find('singles'))->toBeNull();
    });

    test('carries the single’s handle and section data', function () {
        $section = Section::factory()->create([
            'type' => SectionType::Single,
            'name' => 'Homepage',
            'handle' => 'homePage',
        ]);

        $source = EntrySourceLookup::find("section:{$section->uid}");

        expect($source)->not->toBeNull()
            ->and($source['label'])->toBe('Homepage')
            ->and($source['data']['handle'])->toBe('homePage')
            ->and($source['data']['type'])->toBe(SectionType::Single->value)
            ->and($source['data']['section-id'])->toBe($section->id)
            ->and($source['sites'])->toBeArray()
            ->and($source['criteria']['sectionId'])->toBe($section->id);
    });

    test('sorts a single’s index by title, ascending', function () {
        $section = Section::factory()->create(['type' => SectionType::Single]);

        expect(EntrySourceLookup::find("section:{$section->uid}")['defaultSort'])->toBe(['title', 'asc']);
    });

    test('groups singles under a Singles heading', function () {
        $section = Section::factory()->create(['type' => SectionType::Single]);

        $sources = Entry::sources('index');
        $headingIndex = array_find_key($sources, fn ($source) => ($source['heading'] ?? null) === 'Singles');

        expect($headingIndex)->not->toBeNull();

        // Every row between this heading and the next one is a single.
        $keysUnderHeading = [];

        for ($i = $headingIndex + 1; $i < count($sources); $i++) {
            if (isset($sources[$i]['heading'])) {
                break;
            }

            $keysUnderHeading[] = $sources[$i]['key'];
        }

        expect($keysUnderHeading)->toContain("section:{$section->uid}");
    });

    test('stays a real source in the relation-field contexts', function (string $context) {
        $section = Section::factory()->create(['type' => SectionType::Single]);

        expect(EntrySourceLookup::find("section:{$section->uid}", $context))->not->toBeNull();
    })->with(['modal', 'field', 'settings']);
});

describe('single index columns', function () {
    test('omits post date, expiry date and authors for a single’s own source', function () {
        $section = Section::factory()->create(['type' => SectionType::Single]);

        $attributes = Entry::defaultTableAttributes("section:{$section->uid}");

        expect($attributes)->toContain('status')
            ->and($attributes)->toContain('link')
            ->and($attributes)->not->toContain('postDate')
            ->and($attributes)->not->toContain('expiryDate')
            ->and($attributes)->not->toContain('authors');
    });

    test('keeps post date, expiry date and authors for a channel’s source', function () {
        $section = Section::factory()->create(['type' => SectionType::Channel]);

        $attributes = Entry::defaultTableAttributes("section:{$section->uid}");

        expect($attributes)->toContain('postDate')
            ->and($attributes)->toContain('expiryDate')
            ->and($attributes)->toContain('authors');
    });
});

describe('single crumbs', function () {
    test('names the single’s own section', function () {
        actingAs(User::find()->one());

        $section = Section::factory()->create([
            'type' => SectionType::Single,
            'name' => 'Homepage',
            'handle' => 'homePage',
        ]);

        $entry = EntryModel::factory()->forSection($section)->createElement(['title' => 'Homepage']);

        $crumbs = $entry->getCrumbs();

        expect($crumbs[0]['label'])->toBe('Entries')
            ->and($crumbs[1]['label'])->toBe('Homepage');
    });

    test('names a single whose source is disabled', function () {
        actingAs(User::find()->one());

        $section = Section::factory()->create([
            'type' => SectionType::Single,
            'name' => 'Hidden',
            'handle' => 'hiddenPage',
        ]);

        $entry = EntryModel::factory()->forSection($section)->createElement(['title' => 'Hidden']);

        // Pretend the section has no source at all: the crumb falls back to the
        // section's own name rather than disappearing.
        Event::listen(function (ElementSourcesResolving $event) use ($section) {
            if ($event->elementType === Entry::class) {
                $event->sources = array_values(array_filter(
                    $event->sources,
                    fn (array $source) => ($source['key'] ?? null) !== "section:{$section->uid}",
                ));
            }
        });

        $crumbs = $entry->getCrumbs();

        expect($crumbs[1]['label'])->toBe('Hidden')
            ->and($crumbs[1])->not->toHaveKey('href');
    });
});
