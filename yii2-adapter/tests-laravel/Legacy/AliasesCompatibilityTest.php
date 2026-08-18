<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Composer as CoreComposer;
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

it('keeps the bundled Composer fallback in the adapter', function() {
    expect(app(CoreComposer::class))->toBeInstanceOf(Composer::class);
});

it('registers icon aliases from CMS assets', function() {
    expect(Aliases::get('@appicons/craft-cms.svg'))->toEndWith('/icons/custom-icons/craft-cms.svg')
        ->and(Aliases::get('@appicons/github.svg'))->toEndWith('/icons/brands/github.svg');
});
