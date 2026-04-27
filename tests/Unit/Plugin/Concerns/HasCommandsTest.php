<?php

declare(strict_types=1);

use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

beforeEach(function () {
    Artisan::forgetBootstrappers();
    app()->forgetInstance(TestPlugin::class);
});

afterEach(function () {
    Artisan::forgetBootstrappers();
    app()->forgetInstance(TestPlugin::class);
});

it('registers plugin commands when configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->setCommands([TestPluginCommand::class]);
    $plugin->bootHasCommands();

    $artisan = new Artisan(app(), app(Dispatcher::class), app()->version());

    expect($artisan->has('plugin:test'))->toBeTrue();
});

it('does not register commands when none are configured', function () {
    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
    ]);

    $plugin->bootHasCommands();

    $artisan = new Artisan(app(), app(Dispatcher::class), app()->version());

    expect($artisan->has('plugin:test'))->toBeFalse();
});

class TestPluginCommand extends Command
{
    #[Override]
    protected $signature = 'plugin:test';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
