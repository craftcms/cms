<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Data\EntryType as EntryTypeData;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Events\EntryTypeDeleted;
use CraftCms\Cms\Entry\Events\EntryTypeDeleting;
use CraftCms\Cms\Entry\Events\EntryTypeDeletionApplying;
use CraftCms\Cms\Entry\Events\EntryTypeSaved;
use CraftCms\Cms\Entry\Events\EntryTypeSaving;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Json;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

it('normalizes empty ui label formats from project config to the title placeholder', function () {
    $uid = fake()->uuid();

    $this->entryTypes->handleChangedEntryType(new ItemUpdated(
        path: "entryTypes.$uid",
        newValue: [
            'name' => 'Pages',
            'handle' => 'pages',
            'hasTitleField' => true,
            'titleTranslationMethod' => TranslationMethod::Site->value,
            'titleFormat' => null,
            'uiLabelFormat' => '',
        ],
        tokenMatches: [$uid],
    ));

    $found = $this->entryTypes->getEntryTypeByUid($uid);

    expect($found)->toBeInstanceOf(EntryTypeData::class)
        ->and($found->uiLabelFormat)->toBe('{title}');
});

it('can save an entry type', function () {
    Event::fake([
        EntryTypeSaving::class,
        EntryTypeSaved::class,
    ]);

    Event::listen(EntryTypeSaving::class, fn () => null);
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

    Event::assertDispatchedOnce(EntryTypeSaving::class);
    Event::assertDispatchedOnce(EntryTypeSaved::class);
});

it('can delete an entry type by id', function () {
    Event::fake([
        EntryTypeDeleting::class,
        EntryTypeDeletionApplying::class,
        EntryTypeDeleted::class,
    ]);

    Event::listen(EntryTypeDeleting::class, fn () => null);
    Event::listen(EntryTypeDeletionApplying::class, fn () => null);
    Event::listen(EntryTypeDeleted::class, fn () => null);

    $this->entryTypes->saveEntryType($entryType = new EntryTypeData([
        'name' => 'Pages',
        'handle' => 'pages',
    ]));

    expect(EntryType::count())->toBe(1);

    $this->entryTypes->deleteEntryTypeById($entryType->id);
    expect(EntryType::count())->toBe(0);

    Event::assertDispatchedOnce(EntryTypeDeleting::class);
    Event::assertDispatchedOnce(EntryTypeDeletionApplying::class);
    Event::assertDispatchedOnce(EntryTypeDeleted::class);
});

it('restores only entries deleted with their entry type', function () {
    $this->entryTypes->saveEntryType($entryType = new EntryTypeData([
        'name' => 'Pages',
        'handle' => 'pages',
    ]));
    $type = EntryType::findOrFail($entryType->id);
    $section = Section::factory()->withEntryTypes($type)->create();
    Sections::refreshSections();
    $entry = Entry::factory()->forSection($section)->forEntryType($type)->createElement();
    $trashed = Entry::factory()->forSection($section)->forEntryType($type)->createElement();
    Elements::deleteElement($trashed);
    $config = $entryType->getConfig();

    $this->entryTypes->deleteEntryType($entryType);

    expect(EntryType::count())->toBe(0);
    expect(DB::table(Table::ENTRIES)->where('deletedWithEntryType', true)->pluck('id')->all())->toBe([$entry->id]);
    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->not->toBeNull();

    ProjectConfig::set("entryTypes.$entryType->uid", $config);
    event(new RequestHandled(Request::create('/'), new Response));

    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->toBeNull();
    expect(DB::table(Table::ELEMENTS)->where('id', $trashed->id)->value('dateDeleted'))->not->toBeNull();
    expect(Entry::findOrFail($entry->id)->deletedWithEntryType)->toBeFalsy();
});

it('rolls back entry deletion and provenance when layout deletion fails', function () {
    $layout = FieldLayout::factory()->create();
    $type = EntryType::factory()->create(['fieldLayoutId' => $layout->id]);
    $entry = Entry::factory()->forEntryType($type)->createElement();
    $this->entryTypes->refreshEntryTypes();
    Fields::shouldReceive('deleteLayoutById')->once()->with($layout->id)->andThrow(new RuntimeException('Layout deletion failed'));

    expect(fn () => $this->entryTypes->handleDeletedEntryType(new ItemRemoved(
        path: "entryTypes.$type->uid",
        tokenMatches: [$type->uid],
    )))->toThrow(RuntimeException::class, 'Layout deletion failed');

    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->toBeNull();
    expect(Entry::findOrFail($entry->id)->deletedWithEntryType)->toBeFalsy();
    expect(EntryType::find($type->id))->not->toBeNull();
    expect(FieldLayout::find($layout->id))->not->toBeNull();
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
        ->and($pagination['prev_page_url'])->toContain('page=1')
        ->and($pagination['next_page_url'])->toBeNull();
});
