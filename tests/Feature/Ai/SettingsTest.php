<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Agents\AttachmentProbe;
use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Http\Controllers\Settings\SettingsIndexController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Tests\Support\Ai\AltTextTask;
use CraftCms\Cms\Tests\Support\Ai\IndexTask;
use CraftCms\Cms\Tests\Support\Ai\SummarizeTask;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Ai\DescribeTask;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Symfony\Component\DomCrawler\Crawler;

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

it('lists the AI connections screen under the Extend settings', function (): void {
    get(action(SettingsIndexController::class))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.Extend.connections.url', route('craft.cp.settings.ai.index')))
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

it('shows each task’s name, its description in an info icon, and the plugin that registered it', function (): void {
    loadTestPlugin();
    app(AiTasks::class)->register(DescribeTask::class);

    get(route('craft.cp.settings.ai.index'))
        ->assertInertia(function (AssertableInertia $page) {
            $columns = $page->toArray()['props']['ui']['nodes'];
            $values = $page->toArray()['props']['ui']['values']['tasks'];
            $summarize = new Crawler($values['test:summarize']['label']);
            $describe = new Crawler($values['test-plugin:describe']['label']);

            expect(json_encode($columns))->not->toContain('"details"')
                ->and($summarize->text())->toBe('Summarize')
                ->and($summarize->filter('craft-info-icon')->count())->toBe(0)
                ->and($describe->filter('div')->first()->getNode(0)->firstChild->textContent)->toBe('Describe')
                ->and($describe->filter('craft-info-icon')->attr('label'))->toBe('More info about Describe')
                ->and($describe->filter('craft-info-icon')->text())->toBe('Writes <descriptions> of things.')
                ->and($describe->filter('div')->last()->text())->toBe('Test Plugin')
                ->and($describe->filter('div')->last()->filter('span[aria-hidden="true"] svg')->count())->toBe(1);
        })
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

it('lists the tasks that can’t run, and marks providers without an API key', function (): void {
    config(['ai.providers.keyless' => ['driver' => 'anthropic', 'key' => null]]);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_AI, ['tasks' => ['craft:askAi' => ['provider' => 'anthropic']]]);

    $response = get(route('craft.cp.settings.ai.index'))->assertOk();

    $nodes = collect($response->inertiaProps('ui.nodes'));
    $providers = collect($nodes->firstWhere('uid', 'ai-defaults-text')['children'][0]['control']['props']['options'][0]['options']);

    expect($nodes->firstWhere('uid', 'ai-problems-warning')['props']['html'])->toContain('Summarize doesn’t have a provider, and there’s no default text provider.')
        ->and($providers->firstWhere('value', 'keyless')['data']['hint'])->toBe('anthropic · No API key')
        ->and($providers->firstWhere('value', 'anthropic'))->not->toHaveKey('data');
});

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

it('checks that models can see images when tasks that need vision are saved, and says what tasks need', function (): void {
    app(AiTasks::class)->register(AltTextTask::class);
    AttachmentProbe::fake(['I can’t see any image.']);

    post(route('craft.cp.settings.ai.store'), [
        'tasks' => ['test:altText' => ['enabled' => '1', 'provider' => 'anthropic', 'model' => 'claude-text']],
    ])->assertRedirect();

    AttachmentProbe::assertPromptedTimes(1);

    get(route('craft.cp.settings.ai.index'))
        ->assertInertia(function (AssertableInertia $page) {
            $props = $page->toArray()['props']['ui'];

            expect(new Crawler($props['values']['tasks']['test:altText']['label'])->text())->toContain('Needs vision')
                ->and(json_encode($props['nodes'], JSON_UNESCAPED_UNICODE))->toContain('Alt text needs vision, which the claude-text model on the anthropic provider doesn’t support.');
        })
        ->assertOk();
});

it('only checks models when a task’s provider or model changes, or when its last check failed', function (): void {
    app(AiTasks::class)->register(AltTextTask::class);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_AI, [
        'tasks' => ['test:altText' => ['enabled' => true, 'provider' => 'anthropic', 'model' => 'claude-vision']],
    ]);
    $attempts = 0;
    AttachmentProbe::fake(function () use (&$attempts): string {
        $attempts++;

        throw new RuntimeException('Overloaded');
    });
    $save = fn (string $model) => post(route('craft.cp.settings.ai.store'), [
        'tasks' => ['test:altText' => ['enabled' => '1', 'provider' => 'anthropic', 'model' => $model]],
    ])->assertRedirect();

    $save('claude-vision');
    expect($attempts)->toBe(0);

    $save('claude-other');
    expect($attempts)->toBe(1);

    $save('claude-other');
    expect($attempts)->toBe(2);
});

it('shows errors that can be fixed right away after saving, rather than in the settings', function (): void {
    app(AiTasks::class)->register(AltTextTask::class);
    AttachmentProbe::fake([fn () => throw ProviderConnectionException::forProvider('anthropic')]);

    post(route('craft.cp.settings.ai.store'), [
        'tasks' => ['test:altText' => ['enabled' => '1', 'provider' => 'anthropic', 'model' => 'claude-vision']],
    ])->assertRedirect();

    expect(collect(Flash::all())->where('type', 'error')->pluck('message')->implode(''))
        ->toStartWith('Craft couldn’t check whether the claude-vision model on the anthropic provider supports vision:');

    get(route('craft.cp.settings.ai.index'))
        ->assertInertia(fn (AssertableInertia $page) => expect(json_encode($page->toArray()['props']['ui']['nodes'], JSON_UNESCAPED_UNICODE))->not->toContain('couldn’t check'))
        ->assertOk();
});
