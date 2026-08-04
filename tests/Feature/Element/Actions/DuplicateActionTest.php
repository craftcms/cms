<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('duplicates entries via the Laravel perform-action route', function () {
    $entry = EntryModel::factory()->createElement();
    $beforeCount = DB::table(Table::ELEMENTS)->count();

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => Duplicate::class,
        'elementIds' => [$entry->id],
    ])->assertOk();

    expect(DB::table(Table::ELEMENTS)->count())->toBeGreaterThan($beforeCount);
});
