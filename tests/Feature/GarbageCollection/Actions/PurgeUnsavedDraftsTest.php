<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\GarbageCollection\Actions\PurgeUnsavedDrafts;
use Illuminate\Support\Facades\DB;

it('purges unsaved drafts that have gone stale', function () {
    Cms::config()->purgeUnsavedDraftsDuration = 60;

    $savedDraftId = DB::table(Table::DRAFTS)->insertGetId([
        'name' => 'Saved draft',
        'saved' => true,
    ]);
    Element::factory()->create([
        'draftId' => $savedDraftId,
        'dateUpdated' => now()->subSeconds(61),
    ]);

    $unsavedDraftId = DB::table(Table::DRAFTS)->insertGetId([
        'name' => 'Unsaved draft',
        'saved' => false,
    ]);
    Element::factory()->create([
        'draftId' => $unsavedDraftId,
        'dateUpdated' => now()->subSeconds(61),
    ]);

    app(PurgeUnsavedDrafts::class)();

    expect(DB::table(Table::DRAFTS)->find($savedDraftId))->not()->toBeNull();
    expect(DB::table(Table::DRAFTS)->find($unsavedDraftId))->toBeNull();
});
