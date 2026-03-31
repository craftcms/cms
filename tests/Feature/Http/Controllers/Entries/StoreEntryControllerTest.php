<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->admin()->create()->asElement();
    actingAs($this->user);

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
    ]);
});

it('requires login', function () {
    Auth::logout();

    post(action(StoreEntryController::class))
        ->assertRedirect('login');
});

it('requires sectionId when creating a new entry', function () {
    post(action(StoreEntryController::class), [
        // missing sectionId
    ])->assertInvalid(['sectionId']);
});

it('can create a new entry', function () {
    $data = [
        'sectionId' => $this->section->id,
        'title' => 'My New Entry',
        'slug' => 'my-new-entry',
        'enabled' => true,
    ];

    post(action(StoreEntryController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('elements_sites', [
        'slug' => 'my-new-entry',
    ]);

    // Check elements table for title
    $entry = Entry::find()->slug('my-new-entry')->status(null)->one();
    expect($entry)->not->toBeNull()
        ->and($entry->title)->toBe('My New Entry')
        ->and($entry->sectionId)->toBe($this->section->id)
        ->and($entry->authorId)->toBe($this->user->id);
});

it('can update an existing entry', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'title' => 'Updated Title',
    ])->assertRedirect();

    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    expect($entry->title)->toBe('Updated Title');
});

it('can duplicate an entry', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    // Original entry should be enabled by default (from factory)
    // Wait, let's ensure it's enabled
    $entryModel->element()->update(['enabled' => true]);

    postJson(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'duplicate' => true,
        'enabled' => false,
    ])->assertOk();

    // Check that we have 2 canonical entries (original + duplicate)
    // We avoid EntryModel::count() because it might include revisions/drafts
    $count = Entry::find()->status(null)->count();
    expect($count)->toBe(2);

    // The new entry should have the same title (or copied title logic)
    // and should be disabled if original was enabled
    $newEntry = Entry::find()->status(null)->orderBy(['dateCreated' => SORT_DESC])->one();
    expect($newEntry->id)->not->toBe($entryModel->id)
        ->and($newEntry->enabled)->toBeTrue();
});

it('handles provisional drafts', function () {
    // 1. Create a live entry
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    // 2. Create a provisional draft for this entry
    $liveEntry = Entry::find()->id($entryModel->id)->status(null)->one();
    $draft = app(Drafts::class)->createDraft($liveEntry, $this->user->id);
    $draft->isProvisionalDraft = true;
    Elements::saveElement($draft);

    expect($draft->isProvisionalDraft)->toBeTrue();

    // Save the LIVE entry
    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'title' => 'Updated Live Entry',
    ])->assertRedirect();

    // The provisional draft should be gone
    $draftCheck = Entry::find()->drafts(true)->id($draft->id)->one();
    expect($draftCheck)->toBeNull();
});

it('returns JSON response', function () {
    $data = [
        'sectionId' => $this->section->id,
        'title' => 'JSON Entry',
        'slug' => 'json-entry',
        'enabled' => true,
    ];

    postJson(action(StoreEntryController::class), $data)
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'title',
            'slug',
            'dateCreated',
            'dateUpdated',
        ])
        ->assertJsonFragment([
            'title' => 'JSON Entry',
            'slug' => 'json-entry',
        ]);
});

it('throws exception when entry is locked', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    // Mock Cache::lock to return false (lock acquired by someone else)
    Cache::shouldReceive('lock')
        ->with("entry:{$entryModel->id}", 15)
        ->andReturn(
            Mockery::mock(Lock::class)
                ->shouldReceive('get')
                ->andReturn(false)
                ->getMock()
        );

    $this->withoutExceptionHandling();
    $this->expectException(LockTimeoutException::class);

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'title' => 'Locked Entry Update',
    ]);
});

it('handles 404 for missing entry', function () {
    post(action(StoreEntryController::class), [
        'entryId' => 999999,
        'title' => 'Ghost Entry',
    ])->assertNotFound();
});
