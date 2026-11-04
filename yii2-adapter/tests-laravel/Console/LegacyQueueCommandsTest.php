<?php

declare(strict_types=1);

use craft\console\Application as YiiConsoleApplication;
use craft\queue\Command;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Yii2Adapter\Console\LegacyCommandCompatibility;
use CraftCms\Yii2Adapter\Console\LegacyCraftCommand;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Console\Application as Artisan;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

uses(DatabaseTestCase::class);

afterEach(function() {
    Artisan::forgetBootstrappers();
});

function runLegacyCommand(LegacyCraftCommand $command, array $argv): int
{
    $artisan = new Artisan(app(), app('events'), 'testing');
    $artisan->setAutoExit(false);
    $artisan->addCommand($command);

    return $artisan->run(new ArgvInput(['artisan', ...$argv]), new BufferedOutput());
}

it('registers legacy commands whose options collide with Artisan global options', function() {
    $controllerMap = Craft::$app->controllerMap;
    Craft::$app->controllerMap['queue'] = ['class' => Command::class, 'queue' => Craft::$app->getQueue()];

    app()->instance('Craft', Mockery::mock(YiiConsoleApplication::class));
    app()->detectEnvironment(fn() => 'local');
    new LegacyCommandCompatibility()->boot();
    app()->detectEnvironment(fn() => 'testing');
    Craft::$app->controllerMap = $controllerMap;

    $command = new Artisan(app(), app('events'), 'testing')->find('craft:queue:run');
    $command->mergeApplicationDefinition();

    expect($command->getDefinition()->hasOption('job-id'))->toBeTrue()
        ->and($command->getDefinition()->hasOption('verbose'))->toBeTrue();
});

it('maps Artisan global options to their legacy equivalents', function() {
    $app = Mockery::mock(YiiConsoleApplication::class);
    $app->shouldReceive('run')->once()->andReturnUsing(function() {
        expect($_SERVER['argv'])->toBe(['craft', 'queue/run', '--verbose', '--interactive=0', '--color=0', '--job-id=5']);

        return 0;
    });

    $command = new LegacyCraftCommand($app, 'craft:queue:run {--job-id=}', legacyOptions: ['job-id', 'verbose', 'interactive', 'color']);

    expect(runLegacyCommand($command, ['craft:queue:run', '-vv', '--no-interaction', '--no-ansi', '--env', 'local', '--job-id=5']))->toBe(0);
});

it('drops Artisan global options the legacy command does not define', function() {
    $app = Mockery::mock(YiiConsoleApplication::class);
    $app->shouldReceive('run')->once()->andReturnUsing(function() {
        expect($_SERVER['argv'])->toBe(['craft', 'queue/info', '--interactive=0']);

        return 0;
    });

    $command = new LegacyCraftCommand($app, 'craft:queue:info');

    expect(runLegacyCommand($command, ['craft:queue:info', '-v', '-n', '-q']))->toBe(0);
});

it('counts legacy queue totals from the Laravel queue', function() {
    $progress = app(JobProgress::class);
    $progress->queued('pending-one', 'Pending');
    $progress->queued('pending-two', 'Pending');
    $progress->queued('delayed', 'Delayed', delay: 60);
    $progress->queued('reserved', 'Reserved');
    $progress->processing('reserved');
    app(FailedJobProviderInterface::class)->log('database', 'default', '{"uuid":"failed"}', new RuntimeException('Failed'));

    $queue = Craft::$app->getQueue();

    expect($queue->getTotalWaiting())->toBe(2)
        ->and($queue->getTotalDelayed())->toBe(1)
        ->and($queue->getTotalReserved())->toBe(1)
        ->and($queue->getTotalFailed())->toBe(1);
});

it('rejects running a single legacy queue job', function() {
    $command = new Command('queue', Craft::$app, ['queue' => Craft::$app->getQueue()]);

    expect($command->runAction('run', ['jobId' => '1']))->toBe(1);
});
