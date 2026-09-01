<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('updates entry status via the Laravel perform-action route', function () {
    $entry = EntryModel::factory()->createElement();

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => SetStatus::class,
        'elementIds' => [$entry->id],
        'status' => SetStatus::DISABLED,
    ])->assertOk();

    expect(Entry::find()->id($entry->id)->status(Entry::STATUS_DISABLED)->one())
        ->not()->toBeNull();
});

it('returns 400 when action params fail validation', function () {
    $entry = EntryModel::factory()->createElement();

    postJson(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => SetStatus::class,
        'elementIds' => [$entry->id],
    ])->assertBadRequest();
});
