<?php

declare(strict_types=1);

require_once __DIR__.'/GraphqlCommandTestHelpers.php';

beforeEach(function () {
    resetGraphqlCommandState();
});

it('lists no schemas when none exist', function () {
    $this->artisan('craft:graphql:list-schemas')
        ->expectsOutputToContain('No GraphQL schemas exist.')
        ->assertSuccessful();
});

it('lists saved schemas', function () {
    $schemaA = saveGraphqlCommandSchema(['name' => 'Alpha Schema']);
    $schemaB = saveGraphqlCommandSchema(['name' => 'Beta Schema']);

    $this->artisan('craft:graphql:list-schemas')
        ->expectsTable(
            ['UID', 'Name'],
            [
                [$schemaA->uid, $schemaA->name],
                [$schemaB->uid, $schemaB->name],
            ],
        )
        ->assertSuccessful();
});

it('resolves slash aliases', function () {
    $schema = saveGraphqlCommandSchema(['name' => 'Alias Schema']);

    $this->artisan('graphql/list-schemas')
        ->expectsTable(
            ['UID', 'Name'],
            [
                [$schema->uid, $schema->name],
            ],
        )
        ->assertSuccessful();
});
