<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\Elements\Asset;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    app(Gql::class)->flushCaches();
});

it('returns an AssetQuery for top-level resolution with full access schema', function () {
    $volume = Volume::factory()->create();
    gqlActivateFullAccessSchema();

    $query = Asset::prepareQuery(null, []);

    expect($query)->toBeInstanceOf(AssetQuery::class);
});

it('returns empty collection when schema has no volume access', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => [],
    ]));

    $result = Asset::prepareQuery(null, []);

    expect($result)->toBeInstanceOf(ElementCollection::class)
        ->and($result)->toBeEmpty();
});

it('restricts query to allowed volumes based on schema', function () {
    $allowedVolume = Volume::factory()->create();
    $otherVolume = Volume::factory()->create();

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["volumes.{$allowedVolume->uid}:read"],
    ]));

    $query = Asset::prepareQuery(null, []);

    expect($query)->toBeInstanceOf(AssetQuery::class);
});

it('returns preloaded data when source field is not a query', function () {
    gqlActivateFullAccessSchema();

    $preloaded = collect([
        (object) ['id' => 1],
    ]);

    $source = new stdClass;
    $source->assets = $preloaded;

    $result = Asset::prepareQuery($source, [], 'assets');

    expect($result)->toBe($preloaded);
});

it('applies arguments as method calls on the query', function () {
    $volume = Volume::factory()->create();
    gqlActivateFullAccessSchema();

    $query = Asset::prepareQuery(null, [
        'limit' => 5,
    ]);

    expect($query)->toBeInstanceOf(AssetQuery::class);
});

it('ignores null argument values without throwing', function () {
    $volume = Volume::factory()->create();
    gqlActivateFullAccessSchema();

    $query = Asset::prepareQuery(null, [
        'nonExistentMethod' => null,
    ]);

    expect($query)->toBeInstanceOf(AssetQuery::class);
});
