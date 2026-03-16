<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\GqlHelper;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Support\Facades\Artisan;

require_once __DIR__.'/GraphqlCommandTestHelpers.php';

beforeEach(function () {
    resetGraphqlCommandState();
});

it('prints a schema by uid', function () {
    $schema = app(Gql::class)->getPublicSchema();
    $expected = SchemaPrinter::doPrint(app(Gql::class)->getSchemaDef($schema, true));

    expect(Artisan::call('craft:graphql:print-schema', ['--schema' => $schema->uid]))->toBe(0)
        ->and(Artisan::output())->toBe($expected);
});

it('prints a schema by token', function () {
    $schema = app(Gql::class)->getPublicSchema();
    $token = saveGraphqlCommandToken($schema, ['name' => 'Printed Token']);
    $expected = SchemaPrinter::doPrint(app(Gql::class)->getSchemaDef($schema, true));

    expect(Artisan::call('craft:graphql:print-schema', ['--token' => $token->accessToken]))->toBe(0)
        ->and(Artisan::output())->toBe($expected);
});

it('prints the full-access schema', function (string $option) {
    $expected = SchemaPrinter::doPrint(app(Gql::class)->getSchemaDef(GqlHelper::createFullAccessSchema(), true));

    expect(Artisan::call('craft:graphql:print-schema', [$option => true]))->toBe(0)
        ->and(Artisan::output())->toBe($expected);
})->with([
    '--full-schema' => '--full-schema',
    '--fullSchema' => '--fullSchema',
]);

it('fails for an invalid schema uid', function () {
    $this->artisan('craft:graphql:print-schema --schema=invalid-schema')
        ->expectsOutputToContain('Invalid schema UUID: invalid-schema')
        ->assertExitCode(1);
});

it('fails for an invalid authorization token', function () {
    $this->artisan('craft:graphql:print-schema --token=invalid-token')
        ->expectsOutputToContain('Invalid authorization token: invalid-token')
        ->assertExitCode(1);
});
