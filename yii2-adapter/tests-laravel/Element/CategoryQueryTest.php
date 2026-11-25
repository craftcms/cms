<?php

declare(strict_types=1);

use craft\elements\Category;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

afterEach(function() {
    DeprecatedConcepts::resetSupport();
});

it('queries categories with staticStatuses enabled', function() {
    $this->artisan('craft:add-categories-support --force --no-interaction')->assertSuccessful();
    app(GeneralConfig::class)->staticStatuses = true;

    expect(Category::find()->all())->toBe([]);
});
