<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use CraftCms\Cms\View\TwigEngine;
use Illuminate\Support\Facades\File;

afterEach(function () {
    Template::clearResolvedInstance();
});

it('maps plugin view paths to Craft template root names', function () {
    $root = storage_path('framework/testing/plugin-view-root-'.uniqid());
    File::ensureDirectoryExists("{$root}/tokens");
    File::put("{$root}/tokens/index.twig", 'Plugin {{ value }}');

    app(TemplateRoots::class)->register(TemplateMode::Cp, 'mcp', $root);

    TemplateMode::set(TemplateMode::Cp);

    try {
        expect((new TwigEngine)->get("{$root}/tokens/index.twig", ['value' => 'test']))
            ->toBe('Plugin test');
    } finally {
        File::deleteDirectory($root);
    }
});
