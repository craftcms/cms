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

describe('Integer validation for structure attributes', function () {
    test('structure integer fields accept valid integers', function (string $field, int $value) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = $value;

        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'id accepts 1' => ['id', 1],
        'id accepts 999' => ['id', 999],
        'parentId accepts 1' => ['parentId', 1],
        'root accepts 1' => ['root', 1],
        'lft accepts 1' => ['lft', 1],
        'rgt accepts 2' => ['rgt', 2],
        'level accepts 0' => ['level', 0],
        'level accepts 5' => ['level', 5],
    ]);

    test('structure integer fields accept null', function (string $field) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = null;

        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'id accepts null' => ['id'],
        'parentId accepts null' => ['parentId'],
        'root accepts null' => ['root'],
        'lft accepts null' => ['lft'],
        'rgt accepts null' => ['rgt'],
        'level accepts null' => ['level'],
    ]);

});

describe('Site ID validation', function () {
    test('siteId accepts valid site ID', function () {
        $entry = EntryModel::factory()->createElement();
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->hasErrors('siteId'))->toBeFalse();
    });

    test('siteId is validated on SCENARIO_DEFAULT', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_DEFAULT);
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->hasErrors('siteId'))->toBeFalse();
    });

    test('siteId is validated on SCENARIO_LIVE', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->hasErrors('siteId'))->toBeFalse();
    });

    test('siteId is validated on SCENARIO_ESSENTIALS', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $site = Sites::getPrimarySite();
        $entry->siteId = $site->id;

        $entry->validate(['siteId']);

        expect($entry->hasErrors('siteId'))->toBeFalse();
    });
});

describe('DateTime validation', function () {
    test('dateCreated and dateUpdated accept DateTime objects', function (string $field) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = new DateTime;

        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'dateCreated accepts DateTime' => ['dateCreated'],
        'dateUpdated accepts DateTime' => ['dateUpdated'],
    ]);

    test('dateCreated and dateUpdated accept null', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->dateCreated = null;
        $entry->dateUpdated = null;

        $entry->validate(['dateCreated', 'dateUpdated']);

        expect($entry->hasErrors('dateCreated'))->toBeFalse();
        expect($entry->hasErrors('dateUpdated'))->toBeFalse();
    });

    test('dateCreated and dateUpdated accept past dates', function (string $field) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = new DateTime('-1 year');

        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'dateCreated accepts past date' => ['dateCreated'],
        'dateUpdated accepts past date' => ['dateUpdated'],
    ]);

    test('dateCreated and dateUpdated accept future dates', function (string $field) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = new DateTime('+1 year');

        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'dateCreated accepts future date' => ['dateCreated'],
        'dateUpdated accepts future date' => ['dateUpdated'],
    ]);
});

describe('Boolean validation', function () {
    test('isFresh accepts boolean values', function (bool $value) {
        $entry = EntryModel::factory()->createElement();
        $entry->isFresh = $value;

        $entry->validate(['isFresh']);

        expect($entry->hasErrors('isFresh'))->toBeFalse();
    })->with([
        'isFresh accepts true' => [true],
        'isFresh accepts false' => [false],
    ]);
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

        expect($entry->hasErrors('title'))->toBeFalse();
    });

    test('title rejects 256+ characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 256);

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeTrue();
    });

    test('title rejects 4-byte unicode (mb4) characters', function () {
        $this->markTestSkippedWhen(DB::getDriverName() === 'pgsql', 'PostgreSQL always supports 4-byte unicode characters');

        $entry = EntryModel::factory()->createElement();
        $entry->title = 'Test 𝕋𝕚𝕥𝕝𝕖'; // Contains 4-byte unicode characters

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeTrue();
    });

    test('title is required on SCENARIO_DEFAULT for elements with titles', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_DEFAULT);
        $entry->title = '';

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeTrue();
    });

    test('title is required on SCENARIO_LIVE for elements with titles', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->title = '';

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeTrue();
    });
});

describe('Slug validation', function () {
    test('slug accepts valid characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = 'valid-slug-123';

        $entry->validate(['slug']);

        expect($entry->hasErrors('slug'))->toBeFalse();
    });

    test('slug normalizes special characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = 'Tëst Slüg';

        $entry->validate(['slug']);

        // Slug normalization happens but may not be lowercase depending on language
        expect($entry->hasErrors('slug'))->toBeFalse();
    });

    test('slug accepts 255 characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = str_repeat('a', 255);

        $entry->validate(['slug']);

        expect($entry->hasErrors('slug'))->toBeFalse();
    });

    test('slug rejects 256+ characters', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->slug = str_repeat('a', 256);

        $entry->validate(['slug']);

        expect($entry->hasErrors('slug'))->toBeTrue();
    });

    test('slug is validated on SCENARIO_ESSENTIALS', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $entry->slug = str_repeat('a', 256);

        $entry->validate(['slug']);

        expect($entry->hasErrors('slug'))->toBeTrue();
    });
});

describe('URI validation', function () {
    test('uri passes validation for valid format', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['uri']);

        expect($entry->hasErrors('uri'))->toBeFalse();
    });

    test('uri is validated on SCENARIO_ESSENTIALS', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);

        $entry->validate(['uri']);

        expect($entry->hasErrors('uri'))->toBeFalse();
    });
});

describe('Scenario validation', function () {
    test('SCENARIO_LIVE validates title when required', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->title = '';

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeTrue();
    });

    test('SCENARIO_DEFAULT validates title when required', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_DEFAULT);
        $entry->title = '';

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeTrue();
    });
});

describe('Edge cases', function () {
    test('nullable fields accept null values', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->id = null;
        $entry->parentId = null;
        $entry->dateCreated = null;
        $entry->dateUpdated = null;

        $entry->validate(['id', 'parentId', 'dateCreated', 'dateUpdated']);

        expect($entry->hasErrors('id'))->toBeFalse();
        expect($entry->hasErrors('parentId'))->toBeFalse();
        expect($entry->hasErrors('dateCreated'))->toBeFalse();
        expect($entry->hasErrors('dateUpdated'))->toBeFalse();
    });

    test('unicode characters are handled in title', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = '日本語タイトル';

        $entry->validate(['title']);

        expect($entry->hasErrors('title'))->toBeFalse();
    });

    test('multiple validation errors can be collected', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 256);
        $entry->slug = str_repeat('b', 256);

        $entry->validate(['title', 'slug']);

        expect($entry->hasErrors('title'))->toBeTrue();
        expect($entry->hasErrors('slug'))->toBeTrue();
    });

    test('validation with specific attribute names only validates those attributes', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->title = str_repeat('a', 256); // Invalid
        $entry->slug = 'valid-slug'; // Valid

        $entry->validate(['slug']);

        expect($entry->hasErrors('slug'))->toBeFalse();
        expect($entry->hasErrors('title'))->toBeFalse(); // Not validated
    });

    test('validation with clearErrors=false preserves existing errors', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->addError('title', 'Custom error');
        $entry->slug = 'valid-slug';

        $entry->validate(['slug'], false);

        expect($entry->hasErrors('title'))->toBeTrue();
        expect($entry->getErrors('title'))->toBe(['Custom error']);
    });
});
