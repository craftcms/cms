<?php

declare(strict_types=1);

use craft\elements\Category;
use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

it('exposes the structure params and properties of categories to external APIs', function() {
    expect(ElementProperty::readable(Category::class))->toHaveKeys(['structureId', 'level', 'lft', 'rgt', 'root'])
        ->not->toHaveKey('revisionId')
        ->and(ElementQueryParam::forElementType(Category::class))->toHaveKeys(['level', 'ancestorOf', 'descendantOf'])
        ->not->toHaveKey('revisions');
});
