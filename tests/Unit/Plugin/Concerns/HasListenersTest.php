<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    app()->forgetInstance(TestPlugin::class);
});

it('registers configured event listeners', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setListeners([
        TestPluginEvent::class => TestPluginListener::class,
    ]);

    $plugin->bootHasListeners();

    Event::assertListening(TestPluginEvent::class, TestPluginListener::class);
});

it('registers multiple listeners for the same event', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setListeners([
        TestPluginEvent::class => [
            TestPluginListener::class,
            AnotherTestPluginListener::class,
        ],
    ]);

    $plugin->bootHasListeners();

    Event::assertListening(TestPluginEvent::class, TestPluginListener::class);
    Event::assertListening(TestPluginEvent::class, AnotherTestPluginListener::class);
});

class TestPluginEvent {}

class TestPluginListener
{
    public function handle(TestPluginEvent $event): void {}
}

class AnotherTestPluginListener
{
    public function handle(TestPluginEvent $event): void {}
}
