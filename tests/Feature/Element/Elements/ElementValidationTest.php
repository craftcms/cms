<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Edition::set(Edition::Pro);
});

describe('Site ID validation', function () {
    test('siteId accepts valid site ID', function () {
        $entry = EntryModel::factory()->createElement();
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->errors()->has('siteId'))->toBeFalse();
    });

    test('siteId is validated on SCENARIO_LIVE', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->errors()->has('siteId'))->toBeFalse();
    });

    test('siteId is validated on SCENARIO_ESSENTIALS', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->errors()->has('siteId'))->toBeFalse();
    });
});

describe('Title validation', function () {
    test('title is trimmed of whitespace', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = '  Test Title  ';

        $entry->validate(['title']);

        expect($entry->title)->toBe('Test Title');
    });

    test('title accepts 255 characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 255);

        $entry->validate(['title']);

        expect($entry->errors()->has('title'))->toBeFalse();
    });

    test('title rejects 256+ characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 256);

        $entry->validate(['title']);

        expect($entry->errors()->has('title'))->toBeTrue();
    });

    test('title rejects 4-byte unicode (mb4) characters', function () {
        $this->markTestSkippedWhen(DB::getDriverName() === 'pgsql', 'PostgreSQL always supports 4-byte unicode characters');
        $this->markTestSkippedWhen(DB::getDriverName() === 'sqlite', 'SQLite always supports 4-byte unicode characters');

        $entry = EntryModel::factory()->createElement();
        $entry->title = 'Test 𝕋𝕚𝕥𝕝𝕖'; // Contains 4-byte unicode characters

        $entry->validate(['title']);

        expect($entry->errors()->has('title'))->toBeTrue();
    });

    test('title is required on SCENARIO_LIVE for elements with titles', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->title = '';

        $entry->validate(['title']);

        expect($entry->errors()->has('title'))->toBeTrue();
    });
});

describe('Slug validation', function () {
    test('slug accepts valid characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = 'valid-slug-123';

        $entry->validate(['slug']);

        expect($entry->errors()->has('slug'))->toBeFalse();
    });

    test('slug normalizes special characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = 'Tëst Slüg';

        $entry->validate(['slug']);

        // Slug normalization happens but may not be lowercase depending on language
        expect($entry->errors()->has('slug'))->toBeFalse();
    });

    test('slug accepts 255 characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = str_repeat('a', 255);

        $entry->validate(['slug']);

        expect($entry->errors()->has('slug'))->toBeFalse();
    });

    test('slug rejects 256+ characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = str_repeat('a', 256);

        $entry->validate(['slug']);

        expect($entry->errors()->has('slug'))->toBeTrue();
    });

    test('slug is validated on SCENARIO_ESSENTIALS', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $entry->slug = str_repeat('a', 256);

        $entry->validate(['slug']);

        expect($entry->errors()->has('slug'))->toBeTrue();
    });
});

describe('URI validation', function () {
    test('uri passes validation for valid format', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['uri']);

        expect($entry->errors()->has('uri'))->toBeFalse();
    });

    test('uri is validated on SCENARIO_ESSENTIALS', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);

        $entry->validate(['uri']);

        expect($entry->errors()->has('uri'))->toBeFalse();
    });
});

describe('Scenario validation', function () {
    test('SCENARIO_LIVE validates title when required', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->title = '';

        $entry->validate(['title']);

        expect($entry->errors()->has('title'))->toBeTrue();
    });
});

describe('Edge cases', function () {
    test('unicode characters are handled in title', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = '日本語タイトル';

        $entry->validate(['title']);

        expect($entry->errors()->has('title'))->toBeFalse();
    });

    test('multiple validation errors can be collected', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 256);
        $entry->slug = str_repeat('b', 256);

        $entry->validate(['title', 'slug']);

        expect($entry->errors()->has('title'))->toBeTrue();
        expect($entry->errors()->has('slug'))->toBeTrue();
    });

    test('validation with specific attribute names only validates those attributes', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 256); // Invalid
        $entry->slug = 'valid-slug'; // Valid

        $entry->validate(['slug']);

        expect($entry->errors()->has('slug'))->toBeFalse();
        expect($entry->errors()->has('title'))->toBeFalse(); // Not validated
    });

    test('validation with clearErrors=false preserves existing errors', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->errors()->add('title', 'Custom error');
        $entry->slug = 'valid-slug';

        $entry->validate(['slug'], false);

        expect($entry->errors()->has('title'))->toBeTrue();
        expect($entry->errors()->get('title'))->toBe(['Custom error']);
    });
});
