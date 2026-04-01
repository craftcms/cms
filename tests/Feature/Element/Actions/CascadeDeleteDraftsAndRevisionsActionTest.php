<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\CascadeDeleteDraftsAndRevisionsAction;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->action = app(CascadeDeleteDraftsAndRevisionsAction::class);
    $this->drafts = app(Drafts::class);
    $this->revisions = app(Revisions::class);

    actingAs(User::findOne());
});

function createRelatedDraftAndRevisionElements(ElementInterface $canonical): array
{
    $savedDraft = app(Drafts::class)->createDraft($canonical);
    $provisionalDraft = app(Drafts::class)->createDraft($canonical, provisional: true);

    $firstRevisionId = app(Revisions::class)->createRevision($canonical);
    $secondRevisionId = app(Revisions::class)->createRevision($canonical, force: true);

    return [
        'drafts' => [$savedDraft->id, $provisionalDraft->id],
        'revisions' => [$firstRevisionId, $secondRevisionId],
    ];
}

function deletedDatesForElements(array $elementIds): array
{
    return DB::table(Table::ELEMENTS)
        ->whereIn('id', $elementIds)
        ->pluck('dateDeleted', 'id')
        ->all();
}

it('soft deletes all drafts and revisions for the given canonical only', function () {
    $targetCanonical = EntryModel::factory()->createElement();
    $otherCanonical = EntryModel::factory()->createElement();

    $targetElements = createRelatedDraftAndRevisionElements($targetCanonical);
    $otherElements = createRelatedDraftAndRevisionElements($otherCanonical);

    $targetIds = [...$targetElements['drafts'], ...$targetElements['revisions']];
    $otherIds = [...$otherElements['drafts'], ...$otherElements['revisions']];

    expect(deletedDatesForElements([...$targetIds, ...$otherIds]))->each->toBeNull();

    $this->action->handle($targetCanonical->id);

    expect(DB::table(Table::DRAFTS)->count())->toBe(4)
        ->and(DB::table(Table::REVISIONS)->count())->toBe(4)
        ->and(DB::table(Table::ELEMENTS)->where('id', $targetCanonical->id)->value('dateDeleted'))->toBeNull()
        ->and(deletedDatesForElements($targetIds))->each->not->toBeNull()
        ->and(deletedDatesForElements($otherIds))->each->toBeNull();
});

it('restores all drafts and revisions for the given canonical only', function () {
    $targetCanonical = EntryModel::factory()->createElement();
    $otherCanonical = EntryModel::factory()->createElement();

    $targetElements = createRelatedDraftAndRevisionElements($targetCanonical);
    $otherElements = createRelatedDraftAndRevisionElements($otherCanonical);

    $targetIds = [...$targetElements['drafts'], ...$targetElements['revisions']];
    $otherIds = [...$otherElements['drafts'], ...$otherElements['revisions']];

    $this->action->handle($targetCanonical->id);
    $this->action->handle($otherCanonical->id);

    expect(deletedDatesForElements([...$targetIds, ...$otherIds]))->each->not->toBeNull();

    $this->action->handle($targetCanonical->id, delete: false);

    expect(DB::table(Table::ELEMENTS)->where('id', $targetCanonical->id)->value('dateDeleted'))->toBeNull()
        ->and(deletedDatesForElements($targetIds))->each->toBeNull()
        ->and(deletedDatesForElements($otherIds))->each->not->toBeNull();
});

it('does nothing when the canonical has no drafts or revisions', function () {
    $canonical = EntryModel::factory()->createElement();
    $otherCanonical = EntryModel::factory()->createElement();
    $otherElements = createRelatedDraftAndRevisionElements($otherCanonical);
    $otherIds = [...$otherElements['drafts'], ...$otherElements['revisions']];

    $beforeDeletedDates = deletedDatesForElements($otherIds);

    $this->action->handle($canonical->id);
    $this->action->handle($canonical->id, delete: false);

    expect(DB::table(Table::ELEMENTS)->where('id', $canonical->id)->value('dateDeleted'))->toBeNull()
        ->and(deletedDatesForElements($otherIds))->toBe($beforeDeletedDates);
});
