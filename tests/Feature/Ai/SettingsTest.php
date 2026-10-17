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
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config(['ai.providers' => [
        'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
    ]]);
    app(AiTasks::class)->register(SummarizeTask::class);
    actingAs(User::find()->one());
});

it('lists the AI connections screen under the AI settings', function (): void {
    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.AI.connections.url', route('craft.cp.settings.ai.index')))
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

it('rejects providers that do not support the task modality', function (): void {
    config(['ai.providers.voice' => ['driver' => 'eleven', 'key' => 'sk-test']]);

    post(route('craft.cp.settings.ai.store'), [
        'tasks' => ['test:summarize' => ['provider' => 'voice']],
    ])->assertSessionHasErrors('tasks.test:summarize.provider');
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

it('lists the models laravel/ai names for the provider a task uses', function (array $values): void {
    $response = postJson(route('craft.cp.settings.ai.render-ui'), ['values' => $values, 'scope' => []])->assertOk();

    $row = collect($response->json('ui.nodes'))->first(fn (array $node): bool => data_get($node, 'control.path') === ['tasks'])['control']['uis'][0];
    $models = collect($row['nodes'])->first(fn (array $node): bool => $node['control']['path'] === ['tasks', 'test:summarize', 'model'])['control']['props']['options'][0];

    expect($models['label'])->toBe('anthropic')
        ->and($models['options'])->toContain(['label' => 'claude-haiku-5-5', 'value' => 'claude-haiku-5-5', 'data' => ['hint' => 'Cheapest']]);
})->with([
    'own provider' => [['tasks' => ['test:summarize' => ['provider' => 'anthropic']]]],
    'default provider' => [['defaults' => ['text' => ['provider' => 'anthropic']], 'tasks' => ['test:summarize' => ['provider' => '']]]],
]);
