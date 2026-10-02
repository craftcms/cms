<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlMutationTestHelpers.php';

beforeEach(function () {
    gqlDisablePublicToken();
    gqlActivateFullAccessSchema();
});

it('executes the ping mutation through the graphql api', function () {
    graphQL(<<<'GRAPHQL'
mutation {
  ping
}
GRAPHQL)
        ->assertOk()
        ->assertHeader('content-type', 'application/graphql-response+json')
        ->assertJsonPath('data.ping', 'A mutated pong');
});
