<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\Str;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

require_once __DIR__.'/GraphqlCommandTestHelpers.php';

beforeEach(function () {
    resetGraphqlCommandState();
});

it('dumps a schema to the current working directory', function () {
    $schema = app(Gql::class)->getPublicSchema();
    $filename = Str::slug((string) $schema->name, '_').'.graphql';
    $expected = SchemaPrinter::doPrint(app(Gql::class)->getSchemaDef($schema, true));

    withGraphqlCommandWorkingDirectory(function (string $directory) use ($schema, $filename, $expected) {
        expect(Artisan::call('craft:graphql:dump-schema', ['--schema' => $schema->uid]))->toBe(0)
            ->and(Artisan::output())->toContain("Dumping GraphQL schema to $filename")
            ->and(File::get($directory.DIRECTORY_SEPARATOR.$filename))->toBe($expected);
    });
});
