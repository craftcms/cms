<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;

it('records the caller of a plugin API that logs through the legacy deprecator', function() {
    new DeprecatorCompatibilityTestPlugin()->legacyMethod();
    $line = __LINE__ - 1;

    $log = collect(Deprecator::getRequestLogs())->firstWhere('key', 'plugin-legacyMethod');

    expect($log->file)->toBe(__FILE__)
        ->and($log->line)->toBe($line);
});

it('records the line of a top-level call to the legacy deprecator', function() {
    $file = sys_get_temp_dir() . '/craft-legacy-deprecator-test-' . uniqid() . '.php';
    File::put($file, "<?php\n\nCraft::\$app->getDeprecator()->log('top-level', 'Top-level code is deprecated.');\n");

    try {
        require $file;

        $log = collect(Deprecator::getRequestLogs())->firstWhere('key', 'top-level');

        expect(realpath($log->file))->toBe(realpath($file))
            ->and($log->line)->toBe(3);
    } finally {
        File::delete($file);
    }
});

it('records the template line of a plugin API that logs through the legacy deprecator', function() {
    $tempDir = sys_get_temp_dir() . '/craft-legacy-deprecator-test-' . uniqid();
    File::ensureDirectoryExists($tempDir);
    File::put("$tempDir/caller.twig", "Line one\n\n{% do plugin.legacyMethod() %}\n");
    Aliases::set('@templates', $tempDir);

    try {
        app(TemplateManager::class)->renderTemplate('caller', ['plugin' => new DeprecatorCompatibilityTestPlugin()], TemplateMode::Site);

        $log = collect(Deprecator::getRequestLogs())->firstWhere('key', 'plugin-legacyMethod');

        expect(realpath($log->file))->toBe(realpath("$tempDir/caller.twig"))
            ->and($log->line)->toBe(3);
    } finally {
        File::deleteDirectory($tempDir);
    }
});

class DeprecatorCompatibilityTestPlugin
{
    public function legacyMethod(): void
    {
        Craft::$app->getDeprecator()->log('plugin-legacyMethod', 'legacyMethod() is deprecated.');
    }
}
