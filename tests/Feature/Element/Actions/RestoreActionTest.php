<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('restores trashed entries via the Laravel perform-action route', function () {
    $entry = EntryModel::factory()->createElement();
    Elements::deleteElement($entry);
    $elementActions = app(ElementActions::class);
    $body = $elementActions->serializeActionItems([
        $elementActions->createAction(Restore::class, Entry::class),
    ])[0]['action']['body'];

    postJson(action(PerformElementActionController::class), [
        ...$body,
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementIds' => [$entry->id],
        'criteria' => ['trashed' => true],
    ])->assertOk()->assertJsonPath('message', 'Entries restored.');

    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))
        ->toBeNull();
});
