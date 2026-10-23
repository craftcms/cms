<?php

declare(strict_types=1);

use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\models\CategoryGroup;
use craft\models\TagGroup;
use craft\services\Categories as LegacyCategories;
use craft\services\Tags as LegacyTags;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function() {
    new ReflectionProperty(DeprecatedConcepts::class, 'supportsCategories')->setValue(null, true);
    new ReflectionProperty(DeprecatedConcepts::class, 'supportsTags')->setValue(null, true);
    Cms::config()->enableGraphqlCaching = false;
    app(Gql::class)->flushCaches();

    $this->categories = Craft::$app->getCategories();
    $this->tags = Craft::$app->getTags();

    Craft::$app->set('categories', Mockery::mock(LegacyCategories::class, function($mock) {
        $mock->shouldReceive('getAllGroups')->andReturn([
            new CategoryGroup(['id' => 1, 'uid' => 'topics-uid', 'name' => 'Topics', 'handle' => 'topics']),
        ]);
    })->makePartial());
    Craft::$app->set('tags', Mockery::mock(LegacyTags::class, function($mock) {
        $mock->shouldReceive('getAllTagGroups')->andReturn([
            new TagGroup(['id' => 1, 'uid' => 'keywords-uid', 'name' => 'Keywords', 'handle' => 'keywords']),
        ]);
    })->makePartial());
});

afterEach(function() {
    Craft::$app->set('categories', $this->categories);
    Craft::$app->set('tags', $this->tags);
    DeprecatedConcepts::resetSupport();
    app(Gql::class)->flushCaches();
});

it('registers query-only schema components for category and tag groups', function() {
    $components = app(Gql::class)->getAllSchemaComponents();
    $queries = array_merge(...array_values($components['queries']));
    $mutations = array_merge(...array_values($components['mutations']));

    expect($queries)->toHaveKeys(['settings.categorygroups:read', 'settings.taggroups:read'])
        ->and($mutations)->not->toHaveKeys(['settings.categorygroups:read', 'settings.taggroups:read']);
});

it('only adds group fields when their component is in the schema', function(Closure $getFields, string $component) {
    $gql = app(Gql::class);

    $gql->setActiveSchema(new GqlSchema(['scope' => []]));
    expect($getFields())->not->toHaveKey('group');

    $gql->flushCaches();
    $gql->setActiveSchema(new GqlSchema(['scope' => ["$component:read"]]));
    expect($getFields())->toHaveKey('group');
})->with([
    'category group' => [fn() => CategoryInterface::getFieldDefinitions(), 'settings.categorygroups'],
    'tag group' => [fn() => TagInterface::getFieldDefinitions(), 'settings.taggroups'],
]);

it('queries category and tag groups at the top level', function() {
    $result = app(Gql::class)->executeQuery(new GqlSchema([
        'scope' => ['settings.categorygroups:read', 'settings.taggroups:read'],
    ]), '{ categoryGroups { handle } categoryGroup(uid: "topics-uid") { name } tagGroups { handle } tagGroup(handle: "missing") { handle } }');

    expect($result)->not->toHaveKey('errors')
        ->and($result['data'])->toBe([
            'categoryGroups' => [['handle' => 'topics']],
            'categoryGroup' => ['name' => 'Topics'],
            'tagGroups' => [['handle' => 'keywords']],
            'tagGroup' => null,
        ]);
});
