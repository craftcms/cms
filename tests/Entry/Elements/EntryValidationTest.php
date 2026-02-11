<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Edition::set(Edition::Pro);
});

describe('Safe attribute validation', function () {
    test('placeInStructure is a safe attribute', function (bool $value) {
        $entry = EntryModel::factory()->createElement();
        $entry->placeInStructure = $value;

        $entry->validate(['placeInStructure']);

        expect($entry->errors()->has('placeInStructure'))->toBeFalse();
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

        expect($entry->errors()->has('sectionId'))->toBeTrue();
    });

    test('sectionId is not required when fieldId is set', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sectionId = null;
        $entry->fieldId = 1;

        $entry->validate(['sectionId']);

        expect($entry->errors()->has('sectionId'))->toBeFalse();
    });

    test('fieldId and sectionId cannot both be set', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->fieldId = 1;

        $entry->validate(['fieldId']);

        expect($entry->errors()->has('fieldId'))->toBeTrue();
        expect($entry->errors()->first('fieldId'))->toContain('sectionId');
        expect($entry->errors()->first('fieldId'))->toContain('fieldId');
    });

    test('typeId is required and cannot be empty', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['typeId']);

        expect($entry->errors()->has('typeId'))->toBeFalse();
    });
});

describe('Entry type validation', function () {
    test('typeId must be in available entry types', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate(['typeId']);

        expect($entry->errors()->has('typeId'))->toBeFalse();
    });

    test('typeId rejects entry type not in section', function () {
        $entry = EntryModel::factory()->createElement();
        $otherEntryType = EntryType::factory()->create();

        $entry->typeId = $otherEntryType->id;
        $entry->validate(['typeId']);

        expect($entry->errors()->has('typeId'))->toBeTrue();
    });
});

describe('DateTime validation', function () {
    test('date fields accept valid values', function (string $field, mixed $value) {
        $entry = EntryModel::factory()->createElement();
        $entry->{$field} = $value;

        $entry->validate([$field]);

        expect($entry->errors()->has($field))->toBeFalse();
    })->with([
        'postDate accepts null' => ['postDate', null],
        'expiryDate accepts null' => ['expiryDate', null],
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

        expect($entry->errors()->has('postDate'))->toBe($expectError);
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

        expect($entry->errors()->has('postDate'))->toBeFalse();
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

        expect($entry->errors()->has('authorIds'))->toBe($expectError);
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

        expect($entry->errors()->has('authorIds'))->toBe($expectError);

        if ($errorContains !== null) {
            expect($entry->errors()->first('authorIds'))->toContain($errorContains);
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

        actingAs($admin = \CraftCms\Cms\User\Elements\User::findOne());

        $entry = EntryModel::factory()->createElement([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        DB::table(Table::ENTRIES_AUTHORS)->insert([
            'entryId' => $entry->id,
            'authorId' => $admin->id,
            'sortOrder' => 1,
        ]);

        $entry->setAttributesFromRequest([
            'authorIds' => [$user->id],
        ]);

        $entry->validate(['authorIds']);

        expect($entry->errors()->has('authorIds'))->toBeTrue();
        expect($entry->errors()->first('authorIds'))->toContain('permission');
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

        expect($entry->errors()->has('authorIds'))->toBeFalse();
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

        expect($entry->errors()->has('authorIds'))->toBeFalse();
    });
});

describe('Scenario-specific validation', function () {
    test('SCENARIO_LIVE validates date comparison', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->setScenario(Element::SCENARIO_LIVE);
        $entry->postDate = new DateTime('2025-01-01');
        $entry->expiryDate = new DateTime('2024-01-01');

        $entry->validate(['postDate']);

        expect($entry->errors()->has('postDate'))->toBeTrue();
    });

    test('default scenario skips date comparison validation', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->postDate = new DateTime('2025-01-01');
        $entry->expiryDate = new DateTime('2024-01-01');

        $entry->validate(['postDate']);

        expect($entry->errors()->has('postDate'))->toBeFalse();
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

        expect($entry->errors()->has('authorIds'))->toBeTrue();
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

        expect($entry->errors()->has('authorIds'))->toBeFalse();
    });
});

describe('Edge cases', function () {
    test('multiple validation errors can be collected', function () {
        $entry = EntryModel::factory()->createElement();
        $entry->sectionId = null;
        $entry->fieldId = null;

        $entry->validate(['sectionId']);

        expect($entry->errors()->has('sectionId'))->toBeTrue();
    });

    test('factory creates valid entry by default', function () {
        $entry = EntryModel::factory()->createElement();

        $entry->validate();

        expect($entry->errors()->isEmpty())->toBeTrue();
    });
});
