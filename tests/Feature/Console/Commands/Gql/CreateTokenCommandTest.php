<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\DateTimeHelper;

require_once __DIR__.'/GraphqlCommandTestHelpers.php';

beforeEach(function () {
    resetGraphqlCommandState();
});

it('creates a token with explicit options', function () {
    $schema = saveGraphqlCommandSchema(['name' => 'CLI Schema']);
    $expectedExpiry = DateTimeHelper::toDateTime('2030-01-01')?->format('Y-m-d');

    $this->artisan("craft:graphql:create-token {$schema->uid} --name=\"CLI Token\" --expiry=\"2030-01-01\"")
        ->expectsOutputToContain('Token saved:')
        ->assertSuccessful();

    $token = app(Gql::class)->getTokenByName('CLI Token');

    expect($token)->not->toBeNull()
        ->and($token->schemaId)->toBe($schema->id)
        ->and($token->expiryDate?->format('Y-m-d'))->toBe($expectedExpiry);
});

it('prompts for a schema when the schema uid is not provided', function () {
    $schema = saveGraphqlCommandSchema(['name' => 'Chosen Schema']);
    $selection = "{$schema->name} ({$schema->uid})";

    $this->artisan('craft:graphql:create-token')
        ->expectsQuestion('Select a GraphQL schema', $selection)
        ->expectsQuestion('Token name:', 'Selected Token')
        ->expectsConfirmation('Set an expiry date?', 'no')
        ->expectsOutputToContain('Token saved:')
        ->assertSuccessful();

    expect(app(Gql::class)->getTokenByName('Selected Token')?->schemaId)->toBe($schema->id);
});

it('prompts for a token name when one is not provided', function () {
    $schema = saveGraphqlCommandSchema(['name' => 'Prompt Schema']);

    $this->artisan("craft:graphql:create-token {$schema->uid}")
        ->expectsQuestion('Token name:', 'Prompted Token')
        ->expectsConfirmation('Set an expiry date?', 'no')
        ->expectsOutputToContain('Token saved:')
        ->assertSuccessful();

    expect(app(Gql::class)->getTokenByName('Prompted Token'))->not->toBeNull();
});

it('supports interactively setting an expiry date', function () {
    $schema = saveGraphqlCommandSchema(['name' => 'Expiring Schema']);
    $expectedExpiry = DateTimeHelper::toDateTime('2031-02-03')?->format('Y-m-d');

    $this->artisan("craft:graphql:create-token {$schema->uid}")
        ->expectsQuestion('Token name:', 'Expiring Token')
        ->expectsConfirmation('Set an expiry date?', 'yes')
        ->expectsQuestion('Expiry date:', '2031-02-03')
        ->expectsOutputToContain('Token saved:')
        ->assertSuccessful();

    expect(app(Gql::class)->getTokenByName('Expiring Token')?->expiryDate?->format('Y-m-d'))->toBe($expectedExpiry);
});

it('fails for an invalid expiry date', function () {
    $schema = saveGraphqlCommandSchema(['name' => 'Invalid Expiry Schema']);

    $this->artisan("craft:graphql:create-token {$schema->uid} --name=\"Broken Token\" --expiry=\"not-a-date\"")
        ->expectsOutputToContain('Invalid expiry date: not-a-date')
        ->assertExitCode(1);
});
