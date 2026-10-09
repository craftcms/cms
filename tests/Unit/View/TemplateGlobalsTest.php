<?php

declare(strict_types=1);

use CraftCms\Cms\View\Events\TemplateGlobalsResolving;
use CraftCms\Cms\View\TemplateGlobals;
use Illuminate\Support\Facades\Event;

it('resolves template globals once per scope', function () {
    $resolutions = 0;

    Event::listen(TemplateGlobalsResolving::class, function (TemplateGlobalsResolving $event) use (&$resolutions) {
        $event->globals['resolution'] = ++$resolutions;
    });

    $templateGlobals = app(TemplateGlobals::class);

    expect($templateGlobals->resolve()['resolution'])->toBe(1)
        ->and($templateGlobals->resolve()['resolution'])->toBe(1);

    app()->forgetScopedInstances();

    expect(app(TemplateGlobals::class)->resolve()['resolution'])->toBe(2);
});
