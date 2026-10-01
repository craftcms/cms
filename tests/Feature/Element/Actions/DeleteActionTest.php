<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('soft deletes entries via the Laravel perform-action route', function () {
    $entry = EntryModel::factory()->createElement();

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [$entry->id],
    ])->assertOk();

    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))
        ->not()->toBeNull();
});

it('reports a type-aware message when only part of a page selection can be deleted', function (int $failureCount, string $message) {
    $deletedEntry = EntryModel::factory()->createElement();
    $failedEntries = collect(range(1, $failureCount))
        ->map(fn (): Entry => EntryModel::factory()->createElement());

    Event::listen(ElementDeleting::class, function (ElementDeleting $event) use ($failedEntries): void {
        if ($failedEntries->contains('id', $event->element->id)) {
            $event->isValid = false;
        }
    });

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [$deletedEntry->id, ...$failedEntries->pluck('id')->all()],
    ])->assertBadRequest()
        ->assertJsonPath('message', $message);

    expect(DB::table(Table::ELEMENTS)->where('id', $deletedEntry->id)->value('dateDeleted'))
        ->not()->toBeNull();

    foreach ($failedEntries as $entry) {
        expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))
            ->toBeNull();
    }
})->with([
    'one failure' => [1, 'Could not delete 1 entry.'],
    'two failures' => [2, 'Could not delete 2 entries.'],
]);
