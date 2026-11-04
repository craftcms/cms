<?php

declare(strict_types=1);

use CraftCms\Cms\Support\File;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\CompiledViews;
use Illuminate\Support\Facades\View;
use Illuminate\View\Engines\CompilerEngine;

function staleCompiledView(): string
{
    $compiledPath = config('view.compiled');
    File::ensureDirectoryExists($compiledPath);
    $staleView = $compiledPath.'/stale-compiled-view.php';
    File::put($staleView, '<?php echo $headHtml->toHtml(); ?>');

    return $staleView;
}

it('deletes compiled views', function () {
    $staleView = staleCompiledView();

    CompiledViews::clear();

    expect(File::exists($staleView))->toBeFalse()
        ->and(File::isDirectory(config('view.compiled')))->toBeTrue();
});

it('leaves dotfiles in the compiled views directory, like view:clear', function () {
    staleCompiledView();
    $gitignore = config('view.compiled').'/.gitignore';
    File::put($gitignore, "*\n!.gitignore\n");

    CompiledViews::clear();

    expect(File::exists($gitignore))->toBeTrue();
    File::delete($gitignore);
});

it('does nothing when no compiled views directory is configured', function (mixed $path) {
    config(['view.compiled' => $path]);

    expect(CompiledViews::path())->toBeNull();

    CompiledViews::clear();
})->with([false, null, '']);

it('deletes compiled views after running migrations from the web updater', function () {
    $staleView = staleCompiledView();

    app(Updates::class)->runMigrations([]);

    expect(File::exists($staleView))->toBeFalse();
});

it('resets the Blade engine’s record of compiled views', function () {
    $engine = Mockery::mock(CompilerEngine::class);
    $engine->expects('forgetCompiledOrNotExpired');
    View::getEngineResolver()->register('blade', fn () => $engine);

    CompiledViews::clear();
});
