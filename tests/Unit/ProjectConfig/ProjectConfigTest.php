<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\Events\ItemAdded;
use CraftCms\Cms\ProjectConfig\Events\ItemRemoved;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\ServiceProvider;

it('handles registered project config changes', function (string $method, string $eventClass) {
    $projectConfig = app(ProjectConfig::class);
    $handled = null;

    $projectConfig->{$method}('plugin.{uid}', function (ConfigEvent $event) use (&$handled) {
        $handled = [$event->tokenMatches, $event->data];
    }, ['plugin' => 'example']);

    $projectConfig->handleChangeEvent(new $eventClass('plugin.example'));

    expect($handled)->toBe([['example'], ['plugin' => 'example']]);
})->with([
    'registration' => ['onAdd', ItemAdded::class],
    'replacement' => ['onUpdate', ItemUpdated::class],
    'removal' => ['onRemove', ItemRemoved::class],
]);

it('runs equally specific handlers in registration order', function () {
    $projectConfig = app(ProjectConfig::class);
    $handled = [];

    $projectConfig->onAdd('plugin.example', function (ConfigEvent $event) use (&$handled) {
        $handled[] = $event->data['handler'];
    }, ['handler' => 'literal']);
    $projectConfig->onAdd('plugin.{uid}', function (ConfigEvent $event) use (&$handled) {
        $handled[] = $event->data['handler'];
    }, ['handler' => 'token']);

    $projectConfig->handleChangeEvent(new ItemAdded('plugin.example'));

    expect($handled)->toBe(['literal', 'token']);
});

it('runs handlers from least to most specific', function () {
    $projectConfig = app(ProjectConfig::class);
    $handled = [];

    $reflection = new ReflectionClass($projectConfig);
    $reflection->getProperty('_internalConfig')->setValue($projectConfig, new ReadOnlyProjectConfigData([], $projectConfig));
    $reflection->getProperty('_externalConfig')->setValue($projectConfig, new ReadOnlyProjectConfigData(['plugin' => ['example' => true]], $projectConfig));
    $reflection->getProperty('isApplyingExternalChanges')->setValue($projectConfig, true);

    $projectConfig->onAdd('plugin.{uid}', function () use (&$handled) {
        $handled[] = 'child';
    });
    $projectConfig->onAdd('plugin', function () use (&$handled) {
        $handled[] = 'parent';
    });

    $projectConfig->handleChangeEvent(new ItemAdded('plugin.example'));

    expect($handled)->toBe(['parent', 'child']);
});

it('supports handlers registered by service providers', function () {
    $handled = false;
    $handler = function () use (&$handled) {
        $handled = true;
    };

    app()->register(new class(app(), $handler) extends ServiceProvider
    {
        public function __construct($app, private readonly Closure $handler)
        {
            parent::__construct($app);
        }

        public function boot(ProjectConfig $projectConfig): void
        {
            $projectConfig->onAdd('plugin.{uid}', $this->handler);
        }
    });

    app(ProjectConfig::class)->handleChangeEvent(new ItemAdded('plugin.example'));

    expect($handled)->toBeTrue();
});
