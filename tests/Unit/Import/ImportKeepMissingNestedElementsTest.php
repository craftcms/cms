<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Import\Importers\ElementImporter;

it('supports keeping missing nested elements by default for matrix fields', function () {
    expect((new Matrix)->canKeepMissingNestedElements())->toBeTrue();
});

it('supports keeping missing nested elements by default for addresses fields', function () {
    expect((new Addresses)->canKeepMissingNestedElements())->toBeTrue();
});

it('does not support keeping missing nested elements for content block fields', function () {
    expect((new ContentBlock)->canKeepMissingNestedElements())->toBeFalse();
});

it('normalizes a flat list of dot-notation handles into the nested __keep__-leaf shape', function () {
    $importer = ElementImporter::create()->keepMissingNestedElements(['myMatrix', 'some.nested.handle']);

    expect($importer->keepMissingNestedElements)->toBe([
        'myMatrix' => ['__keep__' => true],
        'some' => ['nested' => ['handle' => ['__keep__' => true]]],
    ]);
});
