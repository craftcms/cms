<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\ElementQueryConditionBuilder;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    Cms::config()->enableGraphqlCaching = false;
});

afterEach(function () {
    app(Gql::class)->setActiveSchema();
});

it('scopes eager-loading plans to the sites allowed by the active schema', function () {
    Fields::shouldReceive('getAllFields')->once()->with(false)->andReturn(collect());

    $primarySite = Sites::getPrimarySite();

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["sites.{$primarySite->uid}:read"],
    ]));

    $document = Parser::parse(<<<'GRAPHQL'
query {
  entries {
    children {
      id
    }
  }
}
GRAPHQL);
    $operation = $document->definitions[0];
    $fieldNode = $operation->selectionSet->selections[0];
    $returnType = new ObjectType(['name' => 'EntryInterface', 'fields' => []]);
    $resolveInfo = new ResolveInfo(
        new FieldDefinition(['name' => 'entries', 'type' => Type::listOf($returnType)]),
        new ArrayObject([$fieldNode]),
        new ObjectType(['name' => 'Query', 'fields' => []]),
        ['entries'],
        new Schema([]),
        [],
        null,
        $operation,
        [],
    );

    $builder = new ElementQueryConditionBuilder(['resolveInfo' => $resolveInfo]);
    $builder->setArgumentManager(new ArgumentManager);
    $conditions = $builder->extractQueryConditions();

    expect($conditions['with'][0]->siteIds)->toBe([$primarySite->id]);
});

it('scopes eager-loading plans to no sites when the schema has no site scope', function () {
    Fields::shouldReceive('getAllFields')->once()->with(false)->andReturn(collect());

    app(Gql::class)->setActiveSchema(new GqlSchema(['scope' => []]));

    $document = Parser::parse(<<<'GRAPHQL'
query {
  entries {
    children {
      id
    }
  }
}
GRAPHQL);
    $operation = $document->definitions[0];
    $fieldNode = $operation->selectionSet->selections[0];
    $returnType = new ObjectType(['name' => 'EntryInterface', 'fields' => []]);
    $resolveInfo = new ResolveInfo(
        new FieldDefinition(['name' => 'entries', 'type' => Type::listOf($returnType)]),
        new ArrayObject([$fieldNode]),
        new ObjectType(['name' => 'Query', 'fields' => []]),
        ['entries'],
        new Schema([]),
        [],
        null,
        $operation,
        [],
    );

    $builder = new ElementQueryConditionBuilder(['resolveInfo' => $resolveInfo]);
    $builder->setArgumentManager(new ArgumentManager);
    $conditions = $builder->extractQueryConditions();

    expect($conditions['with'][0]->siteIds)->toBe([]);
});
