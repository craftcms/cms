<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;

it('validates required and unique names and access tokens', function () {
    insertGqlTokenValidationRow([
        'name' => 'Existing Token',
        'accessToken' => 'existing-token',
    ]);

    $duplicateName = new GqlToken([
        'name' => 'Existing Token',
        'accessToken' => 'new-token',
    ]);

    expect($duplicateName->validate(['name', 'accessToken']))->toBeFalse()
        ->and($duplicateName->errors()->has('name'))->toBeTrue();

    $duplicateAccessToken = new GqlToken([
        'name' => 'New Token',
        'accessToken' => 'existing-token',
    ]);

    expect($duplicateAccessToken->validate(['name', 'accessToken']))->toBeFalse()
        ->and($duplicateAccessToken->errors()->has('accessToken'))->toBeTrue();

    $required = new GqlToken;

    expect($required->validate(['name', 'accessToken']))->toBeFalse()
        ->and($required->errors()->has('name'))->toBeTrue()
        ->and($required->errors()->has('accessToken'))->toBeTrue();
});

it('ignores the current id when validating unique names and access tokens', function () {
    $tokenId = insertGqlTokenValidationRow([
        'name' => 'Existing Token',
        'accessToken' => 'existing-token',
    ]);

    $token = new GqlToken([
        'id' => $tokenId,
        'name' => 'Existing Token',
        'accessToken' => 'existing-token',
    ]);

    expect($token->validate(['name', 'accessToken']))->toBeTrue()
        ->and($token->errors()->has('name'))->toBeFalse()
        ->and($token->errors()->has('accessToken'))->toBeFalse();
});

it('tracks token state and resolves schema scope', function () {
    $schema = new GqlSchema([
        'name' => 'Scoped Schema',
        'scope' => ['sections.news:read'],
    ]);

    expect(app(Gql::class)->saveSchema($schema))->toBeTrue();

    $publicToken = new GqlToken([
        'accessToken' => GqlToken::PUBLIC_TOKEN,
        'enabled' => true,
        'schema' => $schema,
    ]);

    expect($publicToken->getIsPublic())->toBeTrue()
        ->and($publicToken->getIsExpired())->toBeFalse()
        ->and($publicToken->getIsValid())->toBeTrue()
        ->and($publicToken->getScope())->toBe(['sections.news:read']);

    $expiredToken = new GqlToken([
        'accessToken' => 'expired-token',
        'enabled' => true,
        'schema' => $schema,
        'expiryDate' => now()->subMinute(),
    ]);

    expect($expiredToken->getIsExpired())->toBeTrue()
        ->and($expiredToken->getIsValid())->toBeFalse();

    $disabledToken = new GqlToken([
        'accessToken' => 'disabled-token',
        'enabled' => false,
        'schema' => $schema,
    ]);

    expect($disabledToken->getIsValid())->toBeFalse();

    $lazyToken = new GqlToken([
        'schemaId' => $schema->id,
    ]);

    expect($lazyToken->getSchema()?->uid)->toBe($schema->uid)
        ->and($lazyToken->getScope())->toBe(['sections.news:read']);
});

function insertGqlTokenValidationRow(array $attributes = []): int
{
    return DB::table(Table::GQLTOKENS)->insertGetId(array_merge([
        'schemaId' => null,
        'name' => 'Token '.Str::random(10),
        'accessToken' => Str::random(20),
        'enabled' => false,
        'expiryDate' => null,
        'lastUsed' => null,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ], $attributes));
}
