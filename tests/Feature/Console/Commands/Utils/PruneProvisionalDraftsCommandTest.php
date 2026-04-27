<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::findOne();

    actingAs($this->user);
});

it('reports when there is nothing to prune', function () {
    $this->artisan('craft:utils:prune-provisional-drafts')
        ->expectsOutputToContain('Nothing to prune.')
        ->assertSuccessful();
});

it('supports a dry run without deleting extra provisional drafts', function () {
    $canonical = EntryModel::factory()->createElement();

    app(Drafts::class)->createDraft($canonical, $this->user->id, provisional: true);
    app(Drafts::class)->createDraft($canonical, $this->user->id, provisional: true);

    $this->artisan('utils/prune-provisional-drafts', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN] Finished pruning extra provisional drafts. 1 provisional draft matched.')
        ->assertSuccessful();

    expect(provisionalDraftCount($canonical->id, $this->user->id))->toBe(2);
});

it('prunes extra provisional drafts and keeps one per element and user', function () {
    $canonical = EntryModel::factory()->createElement();
    $otherCanonical = EntryModel::factory()->createElement();

    app(Drafts::class)->createDraft($canonical, $this->user->id, provisional: true);
    app(Drafts::class)->createDraft($canonical, $this->user->id, provisional: true);
    app(Drafts::class)->createDraft($otherCanonical, $this->user->id, provisional: true);

    $this->artisan('craft:utils:prune-provisional-drafts')
        ->expectsOutputToContain('Finished pruning extra provisional drafts. 1 provisional draft matched.')
        ->assertSuccessful();

    expect(provisionalDraftCount($canonical->id, $this->user->id))->toBe(1);
    expect(provisionalDraftCount($otherCanonical->id, $this->user->id))->toBe(1);
});

function provisionalDraftCount(int $canonicalId, int $creatorId): int
{
    return DB::table(Table::DRAFTS)
        ->where('canonicalId', $canonicalId)
        ->where('creatorId', $creatorId)
        ->where('provisional', true)
        ->count();
}
