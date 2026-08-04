<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Facades\Gql as GqlFacade;
use Illuminate\Testing\TestResponse;

use function CraftCms\Cms\action_url;
use function Pest\Laravel\postJson;

function graphQL(string $query, array $headers = []): TestResponse
{
    return postJson(action_url('graphql/api'), [
        'query' => $query,
    ], headers: array_merge([
        'Content-Type' => 'application/json',
        'Accept' => 'application/graphql-response+json',
    ], $headers));
}

function gqlDisablePublicToken(): void
{
    $token = GqlFacade::getPublicToken();
    $token->enabled = false;

    GqlFacade::saveToken($token);
}

function gqlActivateFullAccessSchema(?string $name = null): void
{
    app(Gql::class)->flushCaches();

    GqlFacade::setActiveSchema(new GqlSchema([
        'name' => $name ?? 'GraphQL '.bin2hex(random_bytes(4)),
        'scope' => GqlHelper::createFullAccessSchema()->scope,
    ]));
}
