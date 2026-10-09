<?php

declare(strict_types=1);

use CraftCms\Cms\Debug\DebugServiceProvider;
use CraftCms\Cms\Debug\Element\ElementCollector;
use CraftCms\Cms\Debug\Element\ElementCollectorProvider;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementRestored;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

it('does not register when debugbar is unavailable', function () {
    $app = new Application;

    new DebugServiceProvider($app)->boot();

    expect($app->bound('debugbar'))->toBeFalse();
});

it('does not register duplicate collectors', function () {
    $debugbar = new LaravelDebugbar(app(), request());
    $debugbar->addCollector(new ElementCollector);

    app()->instance(LaravelDebugbar::class, $debugbar);

    app()->call(ElementCollectorProvider::class);

    expect($debugbar->getCollectors())->toHaveCount(1);
});

it('registers the collector when debugbar resolves later', function () {
    $debugbar = new LaravelDebugbar(app(), request());

    app()->forgetInstance('debugbar');
    app()->forgetInstance(LaravelDebugbar::class);
    app()->singleton('debugbar', fn () => $debugbar);
    app()->instance(LaravelDebugbar::class, $debugbar);

    new DebugServiceProvider(app())->boot();

    expect($debugbar->hasCollector(ElementCollector::NAME))->toBeFalse();

    app('debugbar');

    expect($debugbar->hasCollector(ElementCollector::NAME))->toBeTrue();
});

it('counts element lifecycle events by class', function () {
    $debugbar = new LaravelDebugbar(app(), Request::create('/'));

    app()->instance(LaravelDebugbar::class, $debugbar);

    app()->call(ElementCollectorProvider::class);

    $first = new TestDebugbarElement;
    $second = new TestDebugbarElement;

    event(new ElementsHydrated([$first, $second], []));
    event(new ElementSaved($first, true));
    event(new ElementSaved($first, false));
    event(new ElementDeleted($first));
    event(new ElementRestored($first));

    expect($debugbar->getCollector(ElementCollector::NAME)->collect())->toMatchArray([
        'data' => [
            TestDebugbarElement::class => [
                'retrieved' => 2,
                'created' => 1,
                'updated' => 1,
                'deleted' => 1,
                'restored' => 1,
            ],
        ],
        'count' => 6,
        'key_map' => [
            'retrieved' => 'Retrieved',
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
        ],
        'badges' => [
            'retrieved' => 2,
            'created' => 1,
            'updated' => 1,
            'deleted' => 1,
            'restored' => 1,
        ],
    ]);
});

class TestDebugbarElement extends Element {}
