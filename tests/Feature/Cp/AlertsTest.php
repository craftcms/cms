<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Settings as AiSettings;
use CraftCms\Cms\Asset\Analysis\Tasks\ClassifyImage;
use CraftCms\Cms\Asset\Analysis\Tasks\DetectFocalPoint;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateAltText;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateTitles;
use CraftCms\Cms\Asset\Analysis\Tasks\TagImage;
use CraftCms\Cms\Cp\Alerts;
use CraftCms\Cms\Cp\Events\CpAlertsResolving;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Tests\Support\Ai\SummarizeTask;
use CraftCms\Cms\Update\Data\Updates as UpdatesData;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('returns no alerts for guests', function () {
    auth()->logout();

    $alerts = app(Alerts::class)->get();

    expect($alerts)->toBeArray()->toBeEmpty();
});

it('alerts admins to AI tasks that can’t run, except on the AI settings screen', function (?string $path, bool $alerted) {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => null]]]);
    app(AiTasks::class)->remove(GenerateAltText::class, GenerateTitles::class, DetectFocalPoint::class, TagImage::class, ClassifyImage::class);
    app(AiTasks::class)->register(SummarizeTask::class);
    app()->instance(AiSettings::class, new AiSettings(['tasks' => ['test:summarize' => ['provider' => 'anthropic']]]));
    actingAs(User::findOne());

    $contents = array_map(fn (string|array $alert) => is_array($alert) ? $alert['content'] : $alert, app(Alerts::class)->get(path: $path));

    expect(str_contains(implode(' ', $contents), 'can’t run yet.'))->toBe($alerted);
})->with([
    'another screen' => ['dashboard', true],
    'the AI settings screen' => ['settings/ai', false],
]);

it('merges alerts from CpAlertsResolving listeners', function () {
    actingAs(User::findOne());

    Event::listen(function (CpAlertsResolving $event) {
        $event->alerts[] = 'custom alert from event';
    });

    $alerts = app(Alerts::class)->get(path: 'utilities/updates');
    $contents = array_map(fn (string|array $alert) => is_array($alert) ? $alert['content'] : $alert, $alerts);

    expect(implode(' ', $contents))->toContain('custom alert from event');
});

it('shares alerts with Inertia pages, deferring them until the license and update info are cached', function (bool $licenseInfoCached, bool $updateInfoCached, bool $deferred) {
    actingAs(User::findOne());

    Event::listen(function (CpAlertsResolving $event) {
        $event->alerts[] = 'custom alert from event';
    });

    Cache::forget(License::CACHE_KEY_LICENSE_INFO);
    Cache::forget(Updates::class);

    if ($licenseInfoCached) {
        Cache::put(License::CACHE_KEY_LICENSE_INFO, []);
    }

    if ($updateInfoCached) {
        Cache::put(Updates::class, new UpdatesData);
    }

    $assertAlerts = fn (AssertableInertia $page) => $page
        ->where('alerts.0.content', 'custom alert from event')
        ->where('alerts.0.showIcon', true);

    get(cp_url('dashboard'))->assertInertia(fn (AssertableInertia $page) => $deferred
        ? $page->component('Dashboard')->missing('alerts')->loadDeferredProps($assertAlerts)
        : $assertAlerts($page->component('Dashboard')));
})->with([
    'nothing cached' => [false, false, true],
    'only the license info cached' => [true, false, true],
    'only the update info cached' => [false, true, true],
    'both cached' => [true, true, false],
]);

it('alerts admins when the project config folder can’t be written, instead of failing', function () {
    $blocker = config_path('craft/alerts-write-blocker');
    File::ensureDirectoryExists(dirname($blocker));
    File::put($blocker, '');
    $projectConfig = app(ProjectConfig::class)->writeYamlAutomatically();
    $projectConfig->folderName = 'alerts-write-blocker/project';
    actingAs(User::findOne());

    try {
        $contents = array_map(fn (string|array $alert) => is_array($alert) ? $alert['content'] : $alert, app(Alerts::class)->get(path: 'dashboard'));
    } finally {
        File::delete($blocker);
    }

    expect(implode(' ', $contents))->toContain(t('Your {folder} folder isn’t writable.', [
        'folder' => 'config/alerts-write-blocker/project/',
    ]));
});
