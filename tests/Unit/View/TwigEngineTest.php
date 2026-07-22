<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Template;
use CraftCms\Cms\View\Events\CpTemplateRootsResolving;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TwigEngine;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;

afterEach(function () {
    Template::clearResolvedInstance();
    app(Dispatcher::class)->forget(CpTemplateRootsResolving::class);
    Once::flush();
});

it('maps plugin view paths to Craft template root names', function () {
    $root = storage_path('framework/testing/plugin-view-root-'.uniqid());
    File::ensureDirectoryExists("{$root}/tokens");
    File::put("{$root}/tokens/index.twig", 'Plugin {{ value }}');

    Once::flush();

    Event::listen(CpTemplateRootsResolving::class, function (CpTemplateRootsResolving $event) use ($root) {
        $event->roots['mcp'] = $root;
    });

    TemplateMode::set(TemplateMode::Cp);

    try {
        expect((new TwigEngine)->get("{$root}/tokens/index.twig", ['value' => 'test']))
            ->toBe('Plugin test');
    } finally {
        File::deleteDirectory($root);
    }
});
