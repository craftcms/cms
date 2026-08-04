<?php

declare(strict_types=1);

use craft\console\Application;
use craft\console\Controller;
use craft\console\controllers\ResaveController;
use craft\events\DefineConsoleActionsEvent;
use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use yii\base\Event as YiiEvent;

uses(TestCase::class);

afterEach(function() {
    YiiEvent::off(ResaveController::class, Controller::EVENT_DEFINE_ACTIONS);
});

it('keeps legacy define actions available to command discovery', function() {
    YiiEvent::on(ResaveController::class, Controller::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
        $event->actions['products'] = [
            'helpSummary' => 'Re-saves products.',
            'action' => static fn() => 0,
        ];
    });

    expect(new ResaveController('resave', app('Craft'))->actions())->toHaveKey('products');
});

it('runs legacy commands from nested artisan calls', function() {
    $argv = $_SERVER['argv'];
    $app = Mockery::mock(Application::class);
    $app->shouldReceive('run')->once()->andReturnUsing(function() {
        expect($_SERVER['argv'])->toBe(['craft', 'resave/products', '--limit=10']);

        return 0;
    });

    $command = new LegacyCraftCommand($app, 'craft:resave:products {--limit=}');
    $command->setLaravel(app());

    expect(new CommandTester($command)->execute(['--limit' => 10]))->toBe(0)
        ->and($_SERVER['argv'])->toBe($argv);
});

it('keeps the legacy controller resave api available', function() {
    $controller = new ResaveController('resave', app('Craft'));

    expect(method_exists($controller, 'resaveElements'))->toBeTrue()
        ->and(method_exists($controller, 'saveElements'))->toBeTrue()
        ->and(method_exists($controller, 'actionCategories'))->toBeTrue()
        ->and(method_exists($controller, 'actionTags'))->toBeTrue();
});
