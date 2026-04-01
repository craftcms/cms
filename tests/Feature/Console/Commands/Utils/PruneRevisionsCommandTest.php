<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections as SectionsFacade;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::findOne();

    actingAs($this->user);
});

it('reports when there is nothing to prune', function () {
    $this->artisan('craft:utils:prune-revisions', ['--max-revisions' => 1, '--no-interaction' => true])
        ->expectsOutputToContain('Nothing to prune.')
        ->assertSuccessful();
});

it('supports a dry run without deleting extra revisions', function () {
    $canonical = EntryModel::factory()->createElement();
    $entry = EntryElement::find()->id($canonical->id)->status(null)->one();

    app(Revisions::class)->createRevision($entry, $this->user->id);
    app(Revisions::class)->createRevision($entry, $this->user->id, force: true);

    $this->artisan('utils/prune-revisions', ['--max-revisions' => 1, '--dry-run' => true, '--no-interaction' => true])
        ->expectsOutputToContain('[DRY RUN] Finished pruning revisions. 1 revision matched.')
        ->assertSuccessful();

    expect(revisionCount($canonical->id))->toBe(2);
});

it('prunes revisions for the selected sections only', function () {
    $targetType = EntryType::factory()->create();
    $targetSection = Section::factory()->withEntryTypes($targetType)->create();
    $otherType = EntryType::factory()->create();
    $otherSection = Section::factory()->withEntryTypes($otherType)->create();

    $targetCanonical = EntryModel::factory()->forSection($targetSection)->forEntryType($targetType)->createElement();
    $otherCanonical = EntryModel::factory()->forSection($otherSection)->forEntryType($otherType)->createElement();

    $targetEntry = EntryElement::find()->id($targetCanonical->id)->status(null)->one();
    $otherEntry = EntryElement::find()->id($otherCanonical->id)->status(null)->one();

    app(Revisions::class)->createRevision($targetEntry, $this->user->id);
    app(Revisions::class)->createRevision($targetEntry, $this->user->id, force: true);
    app(Revisions::class)->createRevision($otherEntry, $this->user->id);
    app(Revisions::class)->createRevision($otherEntry, $this->user->id, force: true);

    $this->artisan('craft:utils:prune-revisions', ['--section' => $targetSection->handle, '--max-revisions' => 1])
        ->expectsOutputToContain('Finished pruning revisions. 1 revision matched.')
        ->assertSuccessful();

    expect(revisionCount($targetCanonical->id))->toBe(1);
    expect(revisionCount($otherCanonical->id))->toBe(2);
});

it('prompts for sections when the section option is omitted', function () {
    $targetType = EntryType::factory()->create();
    $targetSection = Section::factory()->withEntryTypes($targetType)->create();
    $otherType = EntryType::factory()->create();
    $otherSection = Section::factory()->withEntryTypes($otherType)->create();

    $targetCanonical = EntryModel::factory()->forSection($targetSection)->forEntryType($targetType)->createElement();
    $otherCanonical = EntryModel::factory()->forSection($otherSection)->forEntryType($otherType)->createElement();

    $targetEntry = EntryElement::find()->id($targetCanonical->id)->status(null)->one();
    $otherEntry = EntryElement::find()->id($otherCanonical->id)->status(null)->one();

    app(Revisions::class)->createRevision($targetEntry, $this->user->id);
    app(Revisions::class)->createRevision($targetEntry, $this->user->id, force: true);
    app(Revisions::class)->createRevision($otherEntry, $this->user->id);
    app(Revisions::class)->createRevision($otherEntry, $this->user->id, force: true);

    $sectionOptions = SectionsFacade::getAllSections()
        ->mapWithKeys(fn ($section) => [$section->handle => sprintf('%s (%s)', $section->name, $section->handle)])
        ->all();

    $this->artisan('craft:utils:prune-revisions')
        ->expectsChoice('Which sections should be pruned?', [$targetSection->handle], $sectionOptions)
        ->expectsQuestion('What is the max number of revisions an element can have?', '1')
        ->expectsOutputToContain('Finished pruning revisions. 1 revision matched.')
        ->assertSuccessful();

    expect(revisionCount($targetCanonical->id))->toBe(1);
    expect(revisionCount($otherCanonical->id))->toBe(2);
});

function revisionCount(int $canonicalId): int
{
    return DB::table(Table::REVISIONS)
        ->where('canonicalId', $canonicalId)
        ->count();
}
