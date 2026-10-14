<?php

declare(strict_types=1);

use craft\records\Site;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use yii\db\Expression;

uses(DatabaseTestCase::class);

it('saves Yii expressions assigned to record attributes as SQL', function() {
    $record = Site::findOne(['primary' => true]);
    $sortOrder = (int) $record->sortOrder;

    $record->sortOrder = new Expression('[[sortOrder]] + 1');

    expect($record->update(false, ['sortOrder']))->toBe(1);
    expect((int) Site::findOne($record->id)->sortOrder)->toBe($sortOrder + 1);
});
