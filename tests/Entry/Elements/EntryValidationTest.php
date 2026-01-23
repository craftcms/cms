<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    Edition::set(Edition::Pro);
});

describe('Integer validation', function () {
    test('integer fields accept valid integers', function (string $field, int $value, ?callable $setup = null) {
        $entry = EntryModel::factory()->createElement();
        $setup?->__invoke($entry);

        $entry->{$field} = $value;
        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'sectionId accepts 1' => ['sectionId', 1],
        'fieldId accepts 1' => ['fieldId', 1, fn ($entry) => $entry->sectionId = null],
        'ownerId accepts 1' => ['ownerId', 1],
        'primaryOwnerId accepts 1' => ['primaryOwnerId', 1],
        'sortOrder accepts 1' => ['sortOrder', 1],
        'sortOrder accepts 0' => ['sortOrder', 0],
    ]);

    test('typeId accepts valid integers from factory', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['typeId']);

        expect($entry->hasErrors('typeId'))->toBeFalse();
    });

    test('nullable integer fields accept null', function (string $field, ?callable $setup = null) {
        $entry = EntryModel::factory()->createElement();
        $setup?->__invoke($entry);

        $entry->{$field} = null;
        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'sectionId accepts null when fieldId is set' => ['sectionId', fn ($entry) => $entry->fieldId = 1],
        'fieldId accepts null' => ['fieldId'],
        'ownerId accepts null' => ['ownerId'],
        'primaryOwnerId accepts null' => ['primaryOwnerId'],
        'sortOrder accepts null' => ['sortOrder'],
    ]);
});

describe('authorIds array validation', function () {
    test('authorIds accepts valid arrays', function (array $authorIds) {
        $entry = EntryModel::factory()->createElement();
        $entry->setAuthorIds($authorIds);

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBeFalse();
    })->with([
        'array of integers' => [[1, 2, 3]],
        'empty array' => [[]],
        'single integer in array' => [[1]],
    ]);
});

describe('Safe attribute validation', function () {
    test('placeInStructure is a safe attribute', function (bool $value) {
        $entry = EntryModel::factory()->createElement();
        $entry->placeInStructure = $value;

        $entry->validate(['placeInStructure']);

        expect($entry->hasErrors('placeInStructure'))->toBeFalse();
    })->with([
        'true' => [true],
        'false' => [false],
    ]);
});

describe('Required field validation', function () {
    test('sectionId is required when fieldId is not set', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sectionId = null;
        $entry->fieldId = null;

        $entry->validate(['sectionId']);

        expect($entry->hasErrors('sectionId'))->toBeTrue();
    });

    test('sectionId is not required when fieldId is set', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sectionId = null;
        $entry->fieldId = 1;

        $entry->validate(['sectionId']);

        expect($entry->hasErrors('sectionId'))->toBeFalse();
    });

    test('typeId is required and cannot be empty', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['typeId']);

        expect($entry->hasErrors('typeId'))->toBeFalse();
    });
});

describe('Entry type validation', function () {
    test('typeId must be in available entry types', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['typeId']);

        expect($entry->hasErrors('typeId'))->toBeFalse();
    });

    test('typeId rejects entry type not in section', function () {
        $entry = EntryModel::factory()->createElement();
        $otherEntryType = EntryType::factory()->create();

        $entry->typeId = $otherEntryType->id;
        $entry->validate(['typeId']);

        expect($entry->hasErrors('typeId'))->toBeTrue();
    });
});

describe('Mutual exclusion validation', function () {
    test('fieldId and sectionId cannot both be set', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->fieldId = 1;

        $entry->validate(['fieldId']);

        expect($entry->hasErrors('fieldId'))->toBeTrue();
        expect($entry->getFirstError('fieldId'))->toContain('sectionId');
        expect($entry->getFirstError('fieldId'))->toContain('fieldId');
    });

    test('fieldId without sectionId is valid', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sectionId = null;
        $entry->fieldId = 1;

        $entry->validate(['fieldId']);

        expect($entry->hasErrors('fieldId'))->toBeFalse();
    });
});

describe('DateTime validation', function () {
    test('date fields accept valid values', function (string $field, mixed $value) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = $value;

        $entry->validate([$field]);

        expect($entry->hasErrors($field))->toBeFalse();
    })->with([
        'postDate accepts DateTime' => ['postDate', new DateTime],
        'postDate accepts null' => ['postDate', null],
        'postDate accepts past dates' => ['postDate', new DateTime('2020-01-01')],
        'postDate accepts future dates' => ['postDate', new DateTime('+1 year')],
        'expiryDate accepts DateTime' => ['expiryDate', new DateTime('+1 year')],
        'expiryDate accepts null' => ['expiryDate', null],
        'expiryDate accepts past dates' => ['expiryDate', new DateTime('2020-01-01')],
        'expiryDate accepts future dates' => ['expiryDate', new DateTime('+1 year')],
    ]);
});

describe('Date comparison validation', function () {
    test('date comparison validation on SCENARIO_LIVE', function (
        ?DateTime $postDate,
        ?DateTime $expiryDate,
        bool $expectError
    ) {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->postDate = $postDate;
        $entry->expiryDate = $expiryDate;

        $entry->validate(['postDate']);

        expect($entry->hasErrors('postDate'))->toBe($expectError);
    })->with([
        'postDate after expiryDate is invalid' => [new DateTime('2025-01-01'), new DateTime('2024-01-01'), true],
        'postDate before expiryDate is valid' => [new DateTime('2024-01-01'), new DateTime('2025-01-01'), false],
        'only postDate set is valid' => [new DateTime('2025-01-01'), null, false],
        'only expiryDate set is valid' => [null, new DateTime('2025-01-01'), false],
    ]);

    test('date comparison does not apply on default scenario', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->postDate = new DateTime('2025-01-01');
        $entry->expiryDate = new DateTime('2024-01-01');

        $entry->validate(['postDate']);

        expect($entry->hasErrors('postDate'))->toBeFalse();
    });
});

describe('Author required validation', function () {
    test('authorIds requirement varies by section type and scenario', function (
        SectionType $sectionType,
        bool $isLiveScenario,
        int $maxAuthors,
        bool $expectError
    ) {
        $section = Section::factory()->create([
            'type' => $sectionType,
            'maxAuthors' => $maxAuthors,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);

        if ($isLiveScenario) {
            $entry->setScenario(Element::SCENARIO_LIVE);
        }

        $entry->setAuthorIds([]);
        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBe($expectError);
    })->with([
        'Channel with SCENARIO_LIVE requires authors' => [SectionType::Channel, true, 1, true],
        'Structure with SCENARIO_LIVE requires authors' => [SectionType::Structure, true, 1, true],
        'Single sections do not require authors' => [SectionType::Single, true, 1, false],
        'Channel with maxAuthors 0 does not require authors' => [SectionType::Channel, true, 0, false],
        'Channel on default scenario does not require authors' => [SectionType::Channel, false, 1, false],
    ]);
});

describe('Author max count validation', function () {
    test('authorIds validates author count against maxAuthors', function (
        int $maxAuthors,
        int $authorCount,
        bool $expectError,
        ?string $errorContains = null
    ) {
        $section = Section::factory()->create([
            'type' => SectionType::Channel,
            'maxAuthors' => $maxAuthors,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $users = [];
        for ($i = 0; $i < $authorCount; $i++) {
            $users[] = User::factory()->create(['admin' => true]);
        }

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->setAuthorIds(array_map(fn ($u) => $u->id, $users));

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBe($expectError);

        if ($errorContains !== null) {
            expect($entry->getFirstError('authorIds'))->toContain($errorContains);
        }
    })->with([
        'rejects more authors than maxAuthors allows (singular)' => [1, 2, true, 'Only one author'],
        'accepts authors within maxAuthors limit' => [2, 2, false],
        'error message uses plural for multiple allowed authors' => [3, 5, true, 'authors are'],
    ]);
});

describe('Author permission validation', function () {
    test('author must have viewEntries permission for the section', function () {
        $section = Section::factory()->create([
            'type' => SectionType::Channel,
            'maxAuthors' => 1,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $user = User::factory()->create(['admin' => false]);

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->setAuthorIds([$user->id]);

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBeTrue();
        expect($entry->getFirstError('authorIds'))->toContain('permission');
    });

    test('author with viewEntries permission is valid', function () {
        $section = Section::factory()->create([
            'type' => SectionType::Channel,
            'maxAuthors' => 1,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $user = User::factory()->create(['admin' => false]);

        Gate::before(function ($authUser, string $ability) use ($user, $section) {
            if ($authUser->id === $user->id && $ability === "viewEntries:{$section->uid}") {
                return true;
            }

            return null;
        });

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->setAuthorIds([$user->id]);

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBeFalse();
    });

    test('admin user can be author without explicit permission', function () {
        $section = Section::factory()->create([
            'type' => SectionType::Channel,
            'maxAuthors' => 1,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $adminUser = User::factory()->create(['admin' => true]);

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->setAuthorIds([$adminUser->id]);

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBeFalse();
    });
});

describe('Scenario-specific validation', function () {
    test('SCENARIO_LIVE validates date comparison', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->postDate = new DateTime('2025-01-01');
        $entry->expiryDate = new DateTime('2024-01-01');

        $entry->validate(['postDate']);

        expect($entry->hasErrors('postDate'))->toBeTrue();
    });

    test('default scenario skips date comparison validation', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->postDate = new DateTime('2025-01-01');
        $entry->expiryDate = new DateTime('2024-01-01');

        $entry->validate(['postDate']);

        expect($entry->hasErrors('postDate'))->toBeFalse();
    });

    test('SCENARIO_LIVE validates author requirement', function () {
        $section = Section::factory()->create([
            'type' => SectionType::Channel,
            'maxAuthors' => 1,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->setAuthorIds([]);

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBeTrue();
    });

    test('default scenario skips author requirement validation', function () {
        $section = Section::factory()->create([
            'type' => SectionType::Channel,
            'maxAuthors' => 1,
        ]);
        $entryType = EntryType::factory()->create();
        $section->entryTypes()->attach($entryType, ['sortOrder' => 1]);

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->setAuthorIds([]);

        $entry->validate(['authorIds']);

        expect($entry->hasErrors('authorIds'))->toBeFalse();
    });
});

describe('Edge cases', function () {
    test('null values are handled gracefully for nullable fields', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->fieldId = null;
        $entry->ownerId = null;
        $entry->primaryOwnerId = null;
        $entry->sortOrder = null;
        $entry->expiryDate = null;

        $entry->validate(['fieldId', 'ownerId', 'primaryOwnerId', 'sortOrder', 'expiryDate']);

        expect($entry->hasErrors('fieldId'))->toBeFalse();
        expect($entry->hasErrors('ownerId'))->toBeFalse();
        expect($entry->hasErrors('primaryOwnerId'))->toBeFalse();
        expect($entry->hasErrors('sortOrder'))->toBeFalse();
        expect($entry->hasErrors('expiryDate'))->toBeFalse();
    });

    test('multiple validation errors can be collected', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sectionId = null;
        $entry->fieldId = null;

        $entry->validate(['sectionId']);

        expect($entry->hasErrors('sectionId'))->toBeTrue();
    });

    test('integer fields accept zero', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sortOrder = 0;

        $entry->validate(['sortOrder']);

        expect($entry->hasErrors('sortOrder'))->toBeFalse();
    });

    test('factory creates valid entry by default', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate();

        expect($entry->hasErrors())->toBeFalse();
    });
});
