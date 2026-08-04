<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->deletions = app(ElementDeletions::class);
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

    $this->deletions->deleteElement($targetCanonical);

    expect(DB::table(Table::DRAFTS)->count())->toBe(4)
        ->and(DB::table(Table::REVISIONS)->count())->toBe(4)
        ->and(DB::table(Table::ELEMENTS)->where('id', $targetCanonical->id)->value('dateDeleted'))->not->toBeNull()
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

    $this->deletions->deleteElement($targetCanonical);
    $this->deletions->deleteElement($otherCanonical);

    expect(deletedDatesForElements([...$targetIds, ...$otherIds]))->each->not->toBeNull();

    $targetCanonical = EntryElement::find()->id($targetCanonical->id)->trashed()->one();

    $this->deletions->restoreElement($targetCanonical);

    expect(DB::table(Table::ELEMENTS)->where('id', $targetCanonical->id)->value('dateDeleted'))->toBeNull()
        ->and(deletedDatesForElements($targetIds))->each->toBeNull()
        ->and(deletedDatesForElements($otherIds))->each->not->toBeNull();
});

it('does not affect unrelated drafts or revisions when the canonical has none', function () {
    $canonical = EntryModel::factory()->createElement();
    $otherCanonical = EntryModel::factory()->createElement();
    $otherElements = createRelatedDraftAndRevisionElements($otherCanonical);
    $otherIds = [...$otherElements['drafts'], ...$otherElements['revisions']];

    $beforeDeletedDates = deletedDatesForElements($otherIds);

    $this->deletions->deleteElement($canonical);

    $canonical = EntryElement::find()->id($canonical->id)->trashed()->one();
    $this->deletions->restoreElement($canonical);

    expect(DB::table(Table::ELEMENTS)->where('id', $canonical->id)->value('dateDeleted'))->toBeNull()
        ->and(deletedDatesForElements($otherIds))->toBe($beforeDeletedDates);
});
