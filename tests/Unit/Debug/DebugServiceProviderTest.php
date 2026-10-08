<?php

declare(strict_types=1);

use CraftCms\Cms\Debug\DebugServiceProvider;
use CraftCms\Cms\Entry\Elements\Entry;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataFormatter\JsonDataFormatter;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;

beforeEach(function () {
    $this->previousFormatter = DataCollector::getDefaultDataFormatter();
});

afterEach(function () {
    DataCollector::setDefaultDataFormatter($this->previousFormatter);
});

it('dumps elements as their identity rather than their whole object', function () {
    $formatter = new JsonDataFormatter;
    $formatter->mergeClonerOptions(['casters' => []]);
    DataCollector::setDefaultDataFormatter($formatter);

    $debugbar = new LaravelDebugbar(app(), request());
    app()->instance('debugbar', $debugbar);
    app()->instance(LaravelDebugbar::class, $debugbar);

    new DebugServiceProvider(app())->boot();

    $entry = new Entry;
    $entry->id = 123;
    $entry->title = 'Homepage';

    $dump = json_encode($formatter->formatVar($entry));

    expect($dump)
        ->toContain('123')
        ->toContain('Homepage')
        ->not->toContain('dateCreated');
});
