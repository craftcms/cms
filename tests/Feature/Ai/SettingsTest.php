<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Tests\Support\Ai\IndexTask;
use CraftCms\Cms\Tests\Support\Ai\SummarizeTask;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function (): void {
    config(['ai.providers' => [
        'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
    ]]);
    app(AiTasks::class)->register(SummarizeTask::class);
    actingAs(User::find()->one());
});

it('lists the AI settings screen under System settings', function (): void {
    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.System.ai.url', route('craft.cp.settings.ai.index')))
        ->assertOk();
});

it('shows registered tasks with their stored mappings', function (): void {
    app(ProjectConfig::class)->set(ProjectConfig::PATH_AI, [
        'tasks' => ['test:summarize' => ['enabled' => false, 'provider' => 'anthropic', 'model' => null]],
    ]);

    get(route('craft.cp.settings.ai.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->where('ui.values.tasks.test:summarize.enabled', '0')
            ->where('ui.values.tasks.test:summarize.provider', 'anthropic'))
        ->assertOk();
});

it('stores settings in project config and keeps mappings of unregistered tasks', function (): void {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->set(ProjectConfig::PATH_AI, [
        'tasks' => ['uninstalled:task' => ['enabled' => false, 'provider' => 'anthropic', 'model' => null]],
    ]);

    post(route('craft.cp.settings.ai.store'), [
        'defaults' => ['text' => ['provider' => 'anthropic', 'model' => '']],
        'tasks' => ['test:summarize' => ['enabled' => '1', 'provider' => '', 'model' => 'claude-small']],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($projectConfig->get('ai.defaults.text'))->toEqual(['provider' => 'anthropic', 'model' => null])
        ->and($projectConfig->get('ai.tasks.test:summarize'))->toEqual(['enabled' => true, 'provider' => null, 'model' => 'claude-small'])
        ->and($projectConfig->get('ai.tasks.uninstalled:task.enabled'))->toBeFalse();
});

it('rejects providers that are not in the ai.providers config', function (): void {
    post(route('craft.cp.settings.ai.store'), [
        'tasks' => ['test:summarize' => ['provider' => 'mistral']],
    ])->assertSessionHasErrors('tasks.test:summarize.provider');

    expect(app(ProjectConfig::class)->get('ai'))->toBeNull();
});

it('accepts providers referenced through environment variables', function (): void {
    putenv('AI_TEST_PROVIDER=anthropic');

    try {
        post(route('craft.cp.settings.ai.store'), [
            'tasks' => ['test:summarize' => ['provider' => '$AI_TEST_PROVIDER']],
        ])->assertSessionHasNoErrors();
    } finally {
        putenv('AI_TEST_PROVIDER');
    }

    expect(app(ProjectConfig::class)->get('ai.tasks.test:summarize.provider'))->toBe('$AI_TEST_PROVIDER');
});

it('warns about re-indexing only when a task uses embeddings', function (bool $hasEmbeddingsTask): void {
    if ($hasEmbeddingsTask) {
        app(AiTasks::class)->register(IndexTask::class);
    }

    get(route('craft.cp.settings.ai.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes', fn ($nodes): bool => collect($nodes)->contains('uid', 'ai-embeddings-warning') === $hasEmbeddingsTask))
        ->assertOk();
})->with(['embeddings task' => true, 'no embeddings task' => false]);
