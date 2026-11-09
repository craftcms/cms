<?php

declare(strict_types=1);

use craft\queue\jobs\ResaveElements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('resaves elements when pushed through the legacy queue', function() {
    $entryId = EntryModel::factory()->create()->id;

    Craft::$app->getQueue()->push(new ResaveElements([
        'elementType' => Entry::class,
        'set' => 'title',
        'to' => '=Resaved Title',
    ]));

    expect(Entry::find()->id($entryId)->status(null)->one()?->title)->toBe('Resaved Title');
});
