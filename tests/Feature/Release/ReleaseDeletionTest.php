<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\ReleaseItem;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->actor = User::findOne();
    actingAs($this->actor);
    $this->releases = app(Releases::class);
    $this->canonical = Entry::factory()->createElement(['title' => 'Release content']);
    $this->draft = app(Drafts::class)->createDraft($this->canonical, $this->actor->id);
    $this->release = $this->releases->create('Launch', now()->addDay(), $this->actor);
    $this->releases->attach($this->release, $this->draft, $this->actor);
});

it('removes deleted drafts and unschedules an emptied release', function (bool $canonical, bool $hardDelete) {
    $this->release = $this->releases->schedule($this->release, now()->addDay(), $this->actor);
    $target = $canonical ? $this->canonical : $this->draft;
    $targetDate = $this->release->targetDate;

    expect(Elements::deleteElement($target, $hardDelete))->toBeTrue();
    $this->release->refresh();

    expect($this->release->items()->exists())->toBeFalse()
        ->and($this->release->status)->toBe(ReleaseStatus::Draft)
        ->and($this->release->publishedAt)->toBeNull()
        ->and($this->release->scheduledById)->toBeNull()
        ->and($this->release->targetDate->equalTo($targetDate))->toBeTrue();

    if (! $hardDelete) {
        expect(Elements::restoreElement($target))->toBeTrue()
            ->and($this->release->items()->exists())->toBeFalse();
    }
})->with([[false, false], [false, true], [true, false], [true, true]]);

it('retains the schedule when another draft remains', function () {
    $canonical = Entry::factory()->createElement(['title' => 'Other content']);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id);
    $this->releases->attach($this->release, $draft, $this->actor);
    $this->releases->schedule($this->release, now()->addDay(), $this->actor);

    expect(app(Drafts::class)->discardDraft($this->draft))->toBeTrue()
        ->and($this->release->fresh()->status)->toBe(ReleaseStatus::Scheduled)
        ->and($this->release->items()->pluck('draftId')->all())->toBe([$draft->draftId]);
});

it('cascades raw draft deletion without removing publication history', function () {
    $published = $this->releases->publish($this->release, $this->actor);
    $history = $published->items()->firstOrFail();
    $canonical = $this->canonical::find()->id($this->canonical->id)->status(null)->one();
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id);
    $release = $this->releases->create('Later release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    DB::table(Table::DRAFTS)->where('id', $draft->draftId)->delete();

    expect($release->items()->exists())->toBeFalse()
        ->and(ReleaseItem::find($history->id)->publishedElementId)->toBe($canonical->id);
});
