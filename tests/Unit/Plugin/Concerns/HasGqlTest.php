<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\GqlDirectives;
use CraftCms\Cms\Gql\GqlMutations;
use CraftCms\Cms\Gql\GqlQueries;
use CraftCms\Cms\Gql\GqlTypes;
use CraftCms\Cms\Gql\Mutations\Mutation;
use CraftCms\Cms\Gql\Queries\Query;
use CraftCms\Cms\Tests\TestClasses\Gql\MockDirective;
use CraftCms\Cms\Tests\TestClasses\Gql\MockType;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

it('registers configured GraphQL extensions', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setGqlTypes([MockType::class]);
    $plugin->setGqlQueries([TestPluginGqlQuery::class]);
    $plugin->setGqlMutations([TestPluginGqlMutation::class]);
    $plugin->setGqlDirectives([MockDirective::class]);
    $plugin->bootHasGql();

    expect(app(GqlTypes::class)->types())->toContain(MockType::class)
        ->and(app(GqlQueries::class)->types())->toContain(TestPluginGqlQuery::class)
        ->and(app(GqlMutations::class)->types())->toContain(TestPluginGqlMutation::class)
        ->and(app(GqlDirectives::class)->types())->toContain(MockDirective::class);
});

class TestPluginGqlQuery extends Query
{
    #[Override]
    public static function getQueries(bool $checkToken = true): array
    {
        return [];
    }
}

class TestPluginGqlMutation extends Mutation
{
    #[Override]
    public static function getMutations(): array
    {
        return [];
    }
}
