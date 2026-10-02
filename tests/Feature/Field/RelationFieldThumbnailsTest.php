<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Events\ThumbUrlResolving;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('forwards mode to the first relation without mutating the original query or splitting the eager cache', function (ImageTransformMode $mode) {
    $first = Asset::factory()->createElement();
    $second = Asset::factory()->createElement();
    $entry = Entry::factory()->withField('images', Assets::class, value: [$first->id, $second->id])
        ->createElementWithFields()->element;
    $otherEntry = Entry::factory()->createElement();
    $entry = $entry::find()->id([$entry->id, $otherEntry->id])->fixedOrder()->all()[0];
    $field = $entry->getFieldLayout()->getFieldByHandle('images');
    $query = $entry->getFieldValue('images');
    $requests = [];
    Event::listen(ThumbUrlResolving::class, function (ThumbUrlResolving $event) use (&$requests) {
        $requests[] = [$event->asset->id, $event->width, $event->height, $event->mode];
        $event->url = '/relation.jpg';
    });

    expect($field->getThumbHtml($query, $entry, 120, $mode))->toContainTag('craft-thumbnail', ['mode' => $mode->value])
        ->and($requests)->toBe([[$first->id, 120, 120, $mode], [$first->id, 240, 240, $mode]]);

    $nextMode = $mode === ImageTransformMode::Fit ? ImageTransformMode::Crop : ImageTransformMode::Fit;
    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        expect($field->getThumbHtml($query, $entry, 120, $nextMode))->toContainTag('craft-thumbnail', ['mode' => $nextMode->value])
            ->and(DB::getQueryLog())->toBeEmpty()
            ->and(array_slice($requests, 2))->toBe([[$first->id, 120, 120, $nextMode], [$first->id, 240, 240, $nextMode]]);
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect(array_column($query->all(), 'id'))->toBe([$first->id, $second->id]);
})->with([
    'crop' => ImageTransformMode::Crop,
    'fit' => ImageTransformMode::Fit,
    'stretch' => ImageTransformMode::Stretch,
    'letterbox' => ImageTransformMode::Letterbox,
]);

it('keeps empty relations nullable and does not search later relations for thumbnails', function () {
    $withoutThumbnail = Entry::factory()->createElement();
    $asset = Asset::factory()->createElement();
    $field = new Assets;
    Event::fake([ThumbUrlResolving::class]);

    expect($field->getThumbHtml(new ElementCollection, $withoutThumbnail, 120, ImageTransformMode::Crop))->toBeNull()
        ->and($field->getThumbHtml(new ElementCollection([$withoutThumbnail, $asset]), $withoutThumbnail, 120, ImageTransformMode::Crop))->toBeNull();
    Event::assertNotDispatched(ThumbUrlResolving::class);
});
