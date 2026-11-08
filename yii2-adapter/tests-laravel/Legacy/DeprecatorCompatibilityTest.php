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

it('records the template line of a plugin API that logs through the legacy deprecator', function() {
    $tempDir = sys_get_temp_dir() . '/craft-legacy-deprecator-test-' . uniqid();
    File::ensureDirectoryExists($tempDir);
    File::put("$tempDir/caller.twig", "Line one\n\n{% do plugin.legacyMethod() %}\n");
    $template = realpath("$tempDir/caller.twig");
    Aliases::set('@templates', $tempDir);

    try {
        app(TemplateManager::class)->renderTemplate('caller', ['plugin' => new DeprecatorCompatibilityTestPlugin()], TemplateMode::Site);
    } finally {
        File::deleteDirectory($tempDir);
    }

    $log = collect(Deprecator::getRequestLogs())->firstWhere('key', 'plugin-legacyMethod');

    expect($log->file)->toBe($template)
        ->and($log->line)->toBe(3);
});

class DeprecatorCompatibilityTestPlugin
{
    public function legacyMethod(): void
    {
        Craft::$app->getDeprecator()->log('plugin-legacyMethod', 'legacyMethod() is deprecated.');
    }
}
