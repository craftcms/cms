<?php

declare(strict_types=1);

use craft\services\Path;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Composer as CoreComposer;
use CraftCms\Cms\Support\File;
use CraftCms\Yii2Adapter\Support\Composer;
use CraftCms\Yii2Adapter\Support\Env;
use CraftCms\Yii2Adapter\Twig\AliasesExtension;

it('resolves aliases through legacy environment parsing', function() {
    Aliases::set('@uploads', '/path/to/uploads');

    try {
        expect(Env::parse('@uploads/image.jpg'))->toBe('/path/to/uploads/image.jpg');
    } finally {
        Aliases::remove('@uploads');
    }
});

it('registers the legacy Twig alias function', function() {
    expect(collect(new AliasesExtension()->getFunctions())->map->getName()->all())->toContain('alias');
});

it('resolves legacy path service paths through aliases', function() {
    $original = Aliases::get('@tests', false);
    $testsPath = File::normalizePath(__DIR__ . '/../Fixtures');
    Aliases::set('@tests', $testsPath);

    try {
        expect(new Path()->getTestsPath())->toBe($testsPath);
    } finally {
        $original === false
            ? Aliases::remove('@tests')
            : Aliases::set('@tests', $original);
    }
});

it('keeps the bundled Composer fallback in the adapter', function() {
    expect(app(CoreComposer::class))->toBeInstanceOf(Composer::class);
});

it('registers icon aliases from CMS assets', function() {
    expect(Aliases::get('@appicons/craft-cms.svg'))->toEndWith('/icons/custom-icons/craft-cms.svg');
});
