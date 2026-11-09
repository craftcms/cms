<?php

declare(strict_types=1);

use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

afterEach(function() {
    DeprecatedConcepts::resetSupport();
});

it('returns no tag groups or global sets when the site has no tags or global sets', function() {
    expect(DeprecatedConcepts::supportsTags())->toBeFalse()
        ->and(DeprecatedConcepts::supportsGlobalSets())->toBeFalse()
        ->and(Craft::$app->getTags()->getAllTagGroups())->toBe([])
        ->and(Craft::$app->getGlobals()->getAllSets())->toBe([]);
});
