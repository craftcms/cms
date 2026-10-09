<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;

it('validates required and unique names', function () {
    insertGqlSchemaValidationRow([
        'name' => 'Existing Schema',
    ]);

    $duplicate = new GqlSchema([
        'name' => 'Existing Schema',
    ]);

    expect($duplicate->validate(['name']))->toBeFalse()
        ->and($duplicate->errors()->has('name'))->toBeTrue();

    $required = new GqlSchema;

    expect($required->validate(['name']))->toBeFalse()
        ->and($required->errors()->has('name'))->toBeTrue();
});

it('ignores the current id when validating unique names', function () {
    $schemaId = insertGqlSchemaValidationRow([
        'name' => 'Existing Schema',
    ]);

    $schema = new GqlSchema([
        'id' => $schemaId,
        'name' => 'Existing Schema',
    ]);

    expect($schema->validate(['name']))->toBeTrue()
        ->and($schema->errors()->has('name'))->toBeFalse();
});

it('reports scope membership and extracted scope pairs', function () {
    $schema = new GqlSchema([
        'scope' => [
            'sections.news:read',
            'sections.blog:read',
            'users:read',
            'volumes.images:edit',
            'invalid',
        ],
    ]);

    expect($schema->has('sections.news:read'))->toBeTrue()
        ->and($schema->has('volumes.images:read'))->toBeFalse()
        ->and($schema->getAllScopePairs())->toBe([
            'read' => [
                'sections' => ['news', 'blog'],
                'users' => true,
            ],
            'edit' => [
                'volumes' => ['images'],
            ],
        ])
        ->and($schema->getAllScopePairsForAction())->toBe([
            'sections' => ['news', 'blog'],
            'users' => true,
        ])
        ->and($schema->getAllScopePairsForAction('edit'))->toBe([
            'volumes' => ['images'],
        ]);
});

it('serializes schema config with optional scope', function () {
    $schema = new GqlSchema([
        'name' => 'Public Schema',
        'isPublic' => true,
    ]);

    expect($schema->getConfig())->toBe([
        'name' => 'Public Schema',
        'isPublic' => true,
    ]);

    $schema->scope = ['sections.news:read'];

    expect($schema->getConfig())->toBe([
        'name' => 'Public Schema',
        'isPublic' => true,
        'scope' => ['sections.news:read'],
    ]);
});

function insertGqlSchemaValidationRow(array $attributes = []): int
{
    return DB::table(Table::GQLSCHEMAS)->insertGetId(array_merge([
        'name' => 'Schema '.Str::random(10),
        'scope' => json_encode([]),
        'isPublic' => false,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ], $attributes));
}
