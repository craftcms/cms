<?php

declare(strict_types=1);

use CraftCms\Cms\Console\Commands\ClearCachesCommand;
use CraftCms\Cms\Console\Commands\UpCommand;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\artisan;

beforeEach(function () {
    // Default cache options aren't registered while tests are running, but craft:up clears this one.
    ClearCaches::add('compiled-templates', [
        'label' => 'Compiled templates',
        'action' => fn () => null,
    ]);
    Artisan::registerCommand(new ClearCachesCommand(
        signature: 'craft:clear-caches:compiled-templates',
        description: 'Compiled templates',
    ));
});

afterEach(function () {
    ClearCaches::flushState();
    UpCommand::flushState();
});

it('runs registered tasks in the order they were added, with their output below their task lines', function () {
    Artisan::registerCommand(new class extends Command
    {
        protected $signature = 'test:up-task';

        public function handle(): void
        {
            $this->components->info('Command message');
        }
    });

    UpCommand::add('Running a closure', function (Factory $components) {
        $components->warn('Closure warning');
    });
    UpCommand::add('Running a command', 'test:up-task');

    expect(Artisan::call('craft:up', ['--noBackup' => true]))->toBe(0)
        ->and(Artisan::output())->toMatch('/Running a closure[^\n]*DONE\n.*Closure warning.*Running a command[^\n]*DONE\n.*Command message/s');
});

it('fails when a registered task fails, but still runs the tasks after it', function (Closure|string $failingTask) {
    $ran = false;

    Artisan::registerCommand(new ClosureCommand('test:up-failing-task', fn () => 1));

    UpCommand::add('Failing', $failingTask);
    UpCommand::add('Succeeding', function () use (&$ran) {
        $ran = true;
    });

    artisan('craft:up', ['--noBackup' => true])->assertFailed();

    expect($ran)->toBeTrue();
})->with([
    'closure returning false' => [fn () => fn () => false],
    'closure throwing' => [fn () => fn () => throw new RuntimeException('Nope')],
    'command exiting non-zero' => ['test:up-failing-task'],
]);

it('clears compiled Blade views', function () {
    // Composer installs package files with their commit's mtime, so an updated
    // Blade view can be older than its stale compiled copy, which Laravel would
    // otherwise keep serving.
    $compiledPath = config('view.compiled');
    File::ensureDirectoryExists($compiledPath);
    $staleView = $compiledPath.'/stale-compiled-view.php';
    File::put($staleView, '<?php echo $headHtml->toHtml(); ?>');

    artisan('craft:up', ['--noBackup' => true])
        ->expectsOutputToContain('Compiled views')
        ->assertSuccessful();

    expect(File::exists($staleView))->toBeFalse();
});

it('succeeds when no compiled views directory is configured', function () {
    config(['view.compiled' => false]);

    artisan('craft:up', ['--noBackup' => true])
        ->doesntExpectOutputToContain('Compiled views')
        ->assertSuccessful();
});
