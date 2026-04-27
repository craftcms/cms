<?php

declare(strict_types=1);

use craft\console\controllers\ResaveController;
use craft\elements\Category;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(TestCase::class);

afterEach(function() {
    DeprecatedConcepts::resetSupport();
});

it('runs the legacy categories controller action when support tables are enabled', function() {
    $this->artisan('craft:add-categories-support --force --no-interaction')->assertSuccessful();
    $this->artisan('craft:add-tags-support --force --no-interaction')->assertSuccessful();
    DeprecatedConcepts::resetSupport();

    $schema = Craft::$app->getDb()->getSchema();
    $primarySiteId = Sites::getPrimarySite()->id;
    $groupId = 101;

    $categoryElementId = Element::factory()->set('type', Category::class)->create()->id;

    DB::table($schema->getRawTableName('categorygroups'))->insert([
        'id' => $groupId,
        'structureId' => Structure::factory()->create()->id,
        'fieldLayoutId' => null,
        'name' => 'Category Group',
        'handle' => 'category-group',
        'defaultPlacement' => 'end',
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'dateDeleted' => null,
        'uid' => (string) Str::uuid(),
    ]);
    DB::table($schema->getRawTableName('categorygroups_sites'))->insert([
        'groupId' => $groupId,
        'siteId' => $primarySiteId,
        'hasUrls' => false,
        'uriFormat' => null,
        'template' => null,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => (string) Str::uuid(),
    ]);
    DB::table($schema->getRawTableName('categories'))->insert([
        'id' => $categoryElementId,
        'groupId' => $groupId,
        'parentId' => null,
        'deletedWithGroup' => null,
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    DB::table(Table::ELEMENTS_SITES)->where('elementId', $categoryElementId)->update(['title' => null]);

    $exitCode = new ResaveController('resave', Craft::$app)->run('categories', [
        'group' => 'category-group',
        'set' => 'title',
        'to' => '=Category Title',
        'ifEmpty' => true,
    ]);

    expect($exitCode)
        ->toBe(0)
        ->and(Category::find()->id($categoryElementId)->one()?->title)
        ->toBe('Category Title');
});
