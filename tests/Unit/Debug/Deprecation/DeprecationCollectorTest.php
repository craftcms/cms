<?php

declare(strict_types=1);

use CraftCms\Cms\Debug\DebugServiceProvider;
use CraftCms\Cms\Debug\Deprecation\DeprecationCollector;
use CraftCms\Cms\Debug\Deprecation\DeprecationCollectorProvider;
use CraftCms\Cms\Deprecator\Deprecator;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;

beforeEach(function () {
    Deprecator::$logTarget = 'db';
    Deprecator::$throwExceptions = false;
});

it('does not register duplicate collectors', function () {
    $debugbar = new LaravelDebugbar(app(), request());
    $debugbar->addCollector(new DeprecationCollector(app(Deprecator::class)));

    app()->instance(LaravelDebugbar::class, $debugbar);

    app()->call(DeprecationCollectorProvider::class);

    expect($debugbar->getCollectors())->toHaveCount(1);
});

it('registers the collector when debugbar resolves later', function () {
    $debugbar = new LaravelDebugbar(app(), request());

    app()->forgetInstance('debugbar');
    app()->forgetInstance(LaravelDebugbar::class);
    app()->singleton('debugbar', fn () => $debugbar);
    app()->instance(LaravelDebugbar::class, $debugbar);

    new DebugServiceProvider(app())->boot();

    expect($debugbar->hasCollector(DeprecationCollector::NAME))->toBeFalse();

    app('debugbar');

    expect($debugbar->hasCollector(DeprecationCollector::NAME))->toBeTrue();
});

it('collects current request deprecations with trace details', function () {
    $deprecator = app(Deprecator::class);

    $deprecator->log('legacy-api', 'Calling `legacyApi()` is deprecated.', __FILE__, 123);

    $data = new DeprecationCollector($deprecator)->collect();

    expect($data['count'])->toBe(1)
        ->and($data['deprecations'][0])->toMatchArray([
            'number' => 1,
            'key' => 'legacy-api',
            'message' => 'Calling `legacyApi()` is deprecated.',
            'file' => __FILE__,
            'line' => 123,
            'origin' => __FILE__.':123',
        ])
        ->and($data['deprecations'][0]['traces'])->toBeArray()
        ->and(count($data['deprecations'][0]['traces']))->toBeGreaterThan(0);
});

it('provides an expandable debugbar widget', function () {
    $collector = new DeprecationCollector(app(Deprecator::class));

    $widgets = $collector->getWidgets();

    expect($widgets[DeprecationCollector::NAME])->toMatchArray([
        'widget' => 'PhpDebugBar.Widgets.CraftDeprecationsWidget',
        'map' => DeprecationCollector::NAME,
        'title' => 'Deprecations',
    ])
        ->and($widgets[DeprecationCollector::NAME.':badge'])->toMatchArray([
            'map' => DeprecationCollector::NAME.'.count',
            'default' => 0,
        ])
        ->and($collector->getAssets()['inline_js'])->toHaveKey('craft-debugbar-deprecations-widget');
});
