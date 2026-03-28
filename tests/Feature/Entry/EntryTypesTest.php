<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Data\EntryType as EntryTypeData;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Events\ApplyingDeleteEntryType;
use CraftCms\Cms\Entry\Events\DeletingEntryType;
use CraftCms\Cms\Entry\Events\EntryTypeDeleted;
use CraftCms\Cms\Entry\Events\EntryTypeSaved;
use CraftCms\Cms\Entry\Events\SavingEntryType;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->entryTypes = app(EntryTypes::class);
});

it('is a singleton', function () {
    expect(EntryTypesFacade::getFacadeRoot())->toBe(app(EntryTypes::class));
    expect($this->entryTypes)->toBe(app(EntryTypes::class));
});

it('can get entry types by section id', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();

    expect($this->entryTypes->getEntryTypesBySectionId($section->id))->toHaveCount(1);
    expect($this->entryTypes->getEntryTypesBySectionId($section->id)->first()->id)->toBe($entryType->id);
});

it('can get all entry types', function () {
    expect($this->entryTypes->getAllEntryTypes())->toBeEmpty();

    EntryType::factory()->create();
    $this->entryTypes->refreshEntryTypes();

    expect($this->entryTypes->getAllEntryTypes())->toHaveCount(1);
});

it('can get an entry type by id', function () {
    $entryType = EntryType::factory()->create();
    $this->entryTypes->refreshEntryTypes();

    expect($this->entryTypes->getEntryTypeById($entryType->id))->toBeInstanceOf(EntryTypeData::class);
    expect($this->entryTypes->getEntryTypeById(999))->toBeNull();
});

it('can get an entry type by uid', function () {
    $entryType = EntryType::factory()->create();
    $this->entryTypes->refreshEntryTypes();

    expect($this->entryTypes->getEntryTypeByUid($entryType->uid))->toBeInstanceOf(EntryTypeData::class);
    expect($this->entryTypes->getEntryTypeByUid('non-existing'))->toBeNull();
});

it('can get an entry type by handle', function () {
    $entryType = EntryType::factory()->create();
    $this->entryTypes->refreshEntryTypes();

    expect($this->entryTypes->getEntryTypeByHandle($entryType->handle))->toBeInstanceOf(EntryTypeData::class);
    expect($this->entryTypes->getEntryTypeByHandle('non-existing'))->toBeNull();
});

it('can get an entry type mixed', function () {
    $entryType = EntryType::factory()->create();

    expect($this->entryTypes->getEntryType($entryType->id))->toBeInstanceOf(EntryTypeData::class);
    expect($this->entryTypes->getEntryType(Json::encode($entryType->toArray())))->toBeInstanceOf(EntryTypeData::class);

    // Overrides
    $found = $this->entryTypes->getEntryType(Json::encode([
        'id' => $entryType->id,
        'name' => 'A different name',
        'handle' => 'A different handle',
    ]));
    expect($found)->toBeInstanceOf(EntryTypeData::class);
    expect($found->name)->toBe('A different name');
    expect($found->handle)->toBe('A different handle');
});

it('can save an entry type', function () {
    Event::fake([
        SavingEntryType::class,
        EntryTypeSaved::class,
    ]);

    Event::listen(SavingEntryType::class, fn () => null);
    Event::listen(EntryTypeSaved::class, fn () => null);

    expect(EntryType::count())->toBe(0);

    $entryType = new EntryTypeData([
        'name' => 'Pages',
        'handle' => 'pages',
    ]);

    $this->entryTypes->saveEntryType($entryType);

    expect(EntryType::count())->toBe(1);
    tap(EntryType::query()->firstOrFail(), function (EntryType $entryType) {
        expect($entryType->name)->toBe('Pages');
        expect($entryType->handle)->toBe('pages');
    });

    Event::assertDispatchedOnce(SavingEntryType::class);
    Event::assertDispatchedOnce(EntryTypeSaved::class);
});

it('can delete an entry type by id', function () {
    Event::fake([
        DeletingEntryType::class,
        ApplyingDeleteEntryType::class,
        EntryTypeDeleted::class,
    ]);

    Event::listen(DeletingEntryType::class, fn () => null);
    Event::listen(ApplyingDeleteEntryType::class, fn () => null);
    Event::listen(EntryTypeDeleted::class, fn () => null);

    $this->entryTypes->saveEntryType($entryType = new EntryTypeData([
        'name' => 'Pages',
        'handle' => 'pages',
    ]));

    expect(EntryType::count())->toBe(1);

    $this->entryTypes->deleteEntryTypeById($entryType->id);
    expect(EntryType::count())->toBe(0);

    Event::assertDispatchedOnce(DeletingEntryType::class);
    Event::assertDispatchedOnce(ApplyingDeleteEntryType::class);
    Event::assertDispatchedOnce(EntryTypeDeleted::class);
});

it('can delete an entry type', function () {
    $this->entryTypes->saveEntryType($entryType = new EntryTypeData([
        'name' => 'Pages',
        'handle' => 'pages',
    ]));

    expect(EntryType::count())->toBe(1);

    $this->entryTypes->deleteEntryType($entryType);

    expect(EntryType::count())->toBe(0);
});

it('can get table data', function () {
    expect($this->entryTypes->getTableData(1, 100))->not()->toBeEmpty();
});

it('returns laravel-style pagination metadata for table data', function () {
    EntryType::factory()->count(3)->create();
    $this->entryTypes->refreshEntryTypes();

    [$pagination] = $this->entryTypes->getTableData(2, 2);

    expect($pagination)
        ->toMatchArray([
            'total' => 3,
            'per_page' => 2,
            'current_page' => 2,
            'last_page' => 2,
            'from' => 3,
            'to' => 3,
        ])
        ->and($pagination['prev_page_url'])->toContain('p=1')
        ->and($pagination['next_page_url'])->toBeNull();
});
