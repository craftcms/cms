<?php

declare(strict_types=1);

use CraftCms\Cms\Console\Commands\ClearCachesCommand;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
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
});

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
