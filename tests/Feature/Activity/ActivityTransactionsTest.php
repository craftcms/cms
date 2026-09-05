<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ActivityTimelineController;
use CraftCms\Cms\Http\Controllers\Elements\SaveElementController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;

beforeEach(function () {
    $this->actingAs(User::findOne());
});

it('preserves content after a failed save and shows a successful retry once in the timeline', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    DB::table(Table::ACTIVITYEVENTS)->delete();
    Exceptions::fake();
    $recordingFails = true;
    ActivityEvent::creating(function (): void {
        throw new RuntimeException('Activity insert failed.');
    });
    $data = [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'title' => 'Changed title',
    ];

    $this->postJson(action([SaveElementController::class, 'store']), $data)->assertServerError();

    expect(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Original title');
    $this->postJson(action(ActivityTimelineController::class), $data)
        ->assertOk()->assertJsonCount(0, 'events');

    $recordingFails = false;
    $this->postJson(action([SaveElementController::class, 'store']), $data)->assertSuccessful();

    expect(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Changed title');
    $this->postJson(action(ActivityTimelineController::class), $data)
        ->assertOk()->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.changes.0.old', 'Original title')
        ->assertJsonPath('events.0.changes.0.new', 'Changed title');
});

it('rolls back entry and draft edits when activity recording fails', function (bool $draft, string $exceptionType) {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);

    if ($draft) {
        $entry = app(Drafts::class)->createDraft($entry, User::findOne()->id);
    }

    DB::table(Table::ACTIVITYEVENTS)->delete();
    ActivityEvent::creating(fn () => throw new $exceptionType('Activity insert failed.'));
    $entry->title = 'Changed title';

    expect(fn () => Elements::saveElement($entry, updateSearchIndex: false))
        ->toThrow($exceptionType, 'Activity insert failed.');

    $saved = Entry::find()->id($entry->id)->drafts(null)->status(null)->one();

    expect($saved->title)->toBe('Original title')
        ->and(ActivityEvent::query()->count())->toBe(0);
})->with(['canonical entry' => false, 'named draft' => true])
    ->with([RuntimeException::class, InvalidArgumentException::class]);

it('rolls back draft creation when activity recording fails', function () {
    $entry = EntryModel::factory()->createElement();
    $elementCount = DB::table(Table::ELEMENTS)->count();
    ActivityEvent::creating(fn () => throw new RuntimeException('Activity insert failed.'));

    expect(fn () => app(Drafts::class)->createDraft($entry, User::findOne()->id))
        ->toThrow(RuntimeException::class, 'Activity insert failed.');

    expect(DB::table(Table::DRAFTS)->count())->toBe(0)
        ->and(DB::table(Table::ELEMENTS)->count())->toBe($elementCount)
        ->and(ActivityEvent::query()->count())->toBe(0);
});

it('rolls back draft application when activity recording fails', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id);
    $draft->title = 'Changed title';
    Elements::saveElement($draft, updateSearchIndex: false);
    DB::table(Table::ACTIVITYEVENTS)->delete();
    ActivityEvent::creating(fn () => throw new RuntimeException('Activity insert failed.'));

    expect(fn () => app(Drafts::class)->applyDraft($draft))
        ->toThrow(RuntimeException::class, 'Activity insert failed.');

    expect(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Original title')
        ->and(DB::table(Table::DRAFTS)->where('id', $draft->draftId)->exists())->toBeTrue()
        ->and(Entry::find()->id($draft->id)->drafts()->status(null)->one()->title)->toBe('Changed title')
        ->and(ActivityEvent::query()->count())->toBe(0);
});

it('rolls back unpublished draft creation when activity recording fails', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();
    ActivityEvent::creating(fn () => throw new RuntimeException('Activity insert failed.'));

    expect(fn () => app(Drafts::class)->saveElementAsDraft($entry, User::findOne()->id))
        ->toThrow(RuntimeException::class, 'Activity insert failed.');

    expect(DB::table(Table::DRAFTS)->count())->toBe(0)
        ->and(Entry::find()->id($entry->id)->status(null)->one()->getIsDraft())->toBeFalse()
        ->and(ActivityEvent::query()->count())->toBe(0);
});

it('returns validation failures without leaving an unpublished draft row', function () {
    $entry = EntryModel::factory()->createElement();
    $entry->title = str_repeat('x', 256);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(app(Drafts::class)->saveElementAsDraft($entry, User::findOne()->id))->toBeFalse()
        ->and($entry->errors()->has('title'))->toBeTrue()
        ->and(DB::table(Table::DRAFTS)->count())->toBe(0)
        ->and(Entry::find()->id($entry->id)->status(null)->one()->getIsDraft())->toBeFalse()
        ->and(ActivityEvent::query()->count())->toBe(0);
});
