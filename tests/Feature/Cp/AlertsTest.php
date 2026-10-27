<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Settings as AiSettings;
use CraftCms\Cms\Asset\Analysis\Tasks\DetectFocalPoint;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateAltText;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateTitles;
use CraftCms\Cms\Asset\Analysis\Tasks\TagImage;
use CraftCms\Cms\Cp\Alerts;
use CraftCms\Cms\Cp\Events\CpAlertsResolving;
use CraftCms\Cms\Tests\Support\Ai\SummarizeTask;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

it('returns no alerts for guests', function () {
    auth()->logout();

    $alerts = app(Alerts::class)->get();

    expect($alerts)->toBeArray()->toBeEmpty();
});

it('alerts admins to AI tasks that can’t run, except on the AI settings screen', function (?string $path, bool $alerted) {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => null]]]);
    app(AiTasks::class)->remove(GenerateAltText::class, GenerateTitles::class, DetectFocalPoint::class, TagImage::class);
    app(AiTasks::class)->register(SummarizeTask::class);
    app()->instance(AiSettings::class, new AiSettings(['tasks' => ['test:summarize' => ['provider' => 'anthropic']]]));
    actingAs(User::findOne());

    $contents = array_map(fn (string|array $alert) => is_array($alert) ? $alert['content'] : $alert, app(Alerts::class)->get(path: $path));

    expect(str_contains(implode(' ', $contents), 'An AI task can’t run yet.'))->toBe($alerted);
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
