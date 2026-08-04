<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\File;

function resetGraphqlCommandState(): void
{
    app(Gql::class)->flushCaches();
    app(Gql::class)->setActiveSchema(new GqlSchema);
    Cms::config()->enableGraphqlCaching = false;
}

function saveGraphqlCommandSchema(array $attributes = []): GqlSchema
{
    $schema = new GqlSchema(array_merge([
        'name' => 'Schema '.Str::random(10),
    ], $attributes));

    expect(app(Gql::class)->saveSchema($schema))->toBeTrue();

    return $schema;
}

function saveGraphqlCommandToken(GqlSchema $schema, array $attributes = []): GqlToken
{
    $token = new GqlToken(array_merge([
        'name' => 'Token '.Str::random(10),
        'schemaId' => $schema->id,
        'accessToken' => app(Gql::class)->generateToken(),
        'enabled' => true,
    ], $attributes));

    expect(app(Gql::class)->saveToken($token))->toBeTrue();

    return $token;
}

function withGraphqlCommandWorkingDirectory(callable $callback): mixed
{
    $cwd = getcwd();
    $directory = storage_path('framework/testing/graphql-commands-'.Str::random(10));

    File::ensureDirectoryExists($directory);

    try {
        chdir($directory);

        return $callback($directory);
    } finally {
        chdir($cwd);
        File::deleteDirectory($directory);
    }
}
