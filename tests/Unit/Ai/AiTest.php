<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Ai;
use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Enums\AiUnavailableReason;
use CraftCms\Cms\Ai\Events\TaskCompleted;
use CraftCms\Cms\Ai\Events\TaskFailed;
use CraftCms\Cms\Ai\Events\TaskRunning;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Jobs\InvokeTaskAgent;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Tests\Support\Ai\ConflictingSummarizeTask;
use CraftCms\Cms\Tests\Support\Ai\HeroImageTask;
use CraftCms\Cms\Tests\Support\Ai\SummarizeTask;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Prompts\ImagePrompt;
use Laravel\Ai\Responses\Data\TextUsage;

function aiSettings(array $config): void
{
    app()->instance(Settings::class, new Settings($config));
}

function registerTask(string $class = SummarizeTask::class): void
{
    app(AiTasks::class)->register($class);
}

function unavailableReason(string $handle): ?AiUnavailableReason
{
    try {
        app(Ai::class)->resolve($handle);
    } catch (AiUnavailableException $e) {
        return $e->reason;
    }

    return null;
}

beforeEach(function (): void {
    config([
        'ai.default' => 'openai',
        'ai.providers' => [
            'openai' => ['driver' => 'openai', 'key' => 'sk-host-app'],
            'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
            'gemini' => ['driver' => 'gemini', 'key' => 'test'],
        ],
    ]);
});

it('resolves the provider and model', function (array $settings, string $provider, ?string $model): void {
    registerTask();
    aiSettings($settings);

    $resolved = app(Ai::class)->resolve('test:summarize');

    expect($resolved->provider)->toBe($provider)
        ->and($resolved->model)->toBe($model);
})->with([
    'default for the modality' => [['defaults' => ['text' => ['provider' => 'anthropic', 'model' => 'claude-default']]], 'anthropic', 'claude-default'],
    'own provider, never the default model' => [['defaults' => ['text' => ['provider' => 'anthropic', 'model' => 'claude-default']], 'tasks' => ['test:summarize' => ['provider' => 'gemini']]], 'gemini', null],
    'own model with the default provider' => [['defaults' => ['text' => ['provider' => 'anthropic', 'model' => 'claude-default']], 'tasks' => ['test:summarize' => ['model' => 'claude-small']]], 'anthropic', 'claude-small'],
]);

it('resolves environment variable references at runtime', function (): void {
    registerTask();
    aiSettings([
        'defaults' => ['text' => ['provider' => '$AI_TEST_PROVIDER', 'model' => '$AI_TEST_MODEL']],
        'tasks' => ['test:summarize' => ['enabled' => '$AI_TEST_ENABLED']],
    ]);
    putenv('AI_TEST_PROVIDER=gemini');
    putenv('AI_TEST_MODEL=gemini-fast');
    putenv('AI_TEST_ENABLED=true');

    try {
        $resolved = app(Ai::class)->resolve('test:summarize');

        expect($resolved->provider)->toBe('gemini')
            ->and($resolved->model)->toBe('gemini-fast');

        putenv('AI_TEST_ENABLED=false');

        expect(unavailableReason('test:summarize'))->toBe(AiUnavailableReason::TaskDisabled);
    } finally {
        putenv('AI_TEST_PROVIDER');
        putenv('AI_TEST_MODEL');
        putenv('AI_TEST_ENABLED');
    }
});

it('reports why a task is unavailable', function (array $settings, string $handle, AiUnavailableReason $reason): void {
    registerTask();
    aiSettings($settings);

    expect(unavailableReason($handle))->toBe($reason)
        ->and(app(Ai::class)->available($handle))->toBeFalse();
})->with([
    'nothing selected in Craft, despite the host app’s ai.default' => [[], 'test:summarize', AiUnavailableReason::NoDefault],
    'unknown task' => [['defaults' => ['text' => ['provider' => 'anthropic']]], 'test:unknown', AiUnavailableReason::UnknownTask],
    'disabled task' => [['defaults' => ['text' => ['provider' => 'anthropic']], 'tasks' => ['test:summarize' => ['enabled' => false]]], 'test:summarize', AiUnavailableReason::TaskDisabled],
    'default for another modality only' => [['defaults' => ['image' => ['provider' => 'gemini']]], 'test:summarize', AiUnavailableReason::NoDefault],
    'provider missing from config' => [['defaults' => ['text' => ['provider' => 'mistral']]], 'test:summarize', AiUnavailableReason::UnknownProvider],
]);

it('lists the problems admins need to fix', function (array $providers, array $settings, ?string $problem): void {
    config(['ai.providers' => [...config('ai.providers'), ...$providers]]);
    registerTask();
    aiSettings($settings);

    expect(app(Ai::class)->problems()['test:summarize'] ?? null)->toBe($problem);
})->with([
    'resolves, with a key' => [[], ['defaults' => ['text' => ['provider' => 'anthropic']]], null],
    'disabled' => [[], ['tasks' => ['test:summarize' => ['enabled' => false]]], null],
    'nothing chosen, so AI is off' => [[], [], null],
    'no provider, while another task has one' => [[], ['tasks' => ['craft:askAi' => ['provider' => 'anthropic']]], 'Summarize doesn’t have a provider, and there’s no default text provider.'],
    'provider missing from config' => [[], ['tasks' => ['test:summarize' => ['provider' => 'mistral']]], 'Summarize uses the mistral provider, which isn’t defined in config/ai.php.'],
    'null key' => [['claude' => ['driver' => 'anthropic', 'key' => null]], ['tasks' => ['test:summarize' => ['provider' => 'claude']]], 'Summarize uses the claude provider, which doesn’t have an API key.'],
    'empty key' => [['claude' => ['driver' => 'anthropic', 'key' => '']], ['tasks' => ['test:summarize' => ['provider' => 'claude']]], 'Summarize uses the claude provider, which doesn’t have an API key.'],
    'keyless driver' => [['local' => ['driver' => 'ollama', 'key' => '']], ['tasks' => ['test:summarize' => ['provider' => 'local']]], null],
    'no key setting, such as a custom driver' => [['custom' => ['driver' => 'custom']], ['tasks' => ['test:summarize' => ['provider' => 'custom']]], null],
]);

it('rejects a second task with the same handle', function (): void {
    registerTask();

    registerTask(ConflictingSummarizeTask::class);
})->throws(InvalidArgumentException::class);

it('prompts agents with the resolved provider and model', function (): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic', 'model' => 'claude-small']]]);
    AnonymousAgent::fake(['A short summary.']);

    app(Ai::class)->task('test:summarize')->prompt(new AnonymousAgent('Summarize.', [], []), 'Long text');

    AnonymousAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->provider->name() === 'anthropic'
        && $prompt->model === 'claude-small');
});

it('generates media with the provider for the task’s modality', function (): void {
    registerTask(HeroImageTask::class);
    aiSettings(['defaults' => ['image' => ['provider' => 'gemini', 'model' => 'gemini-image']]]);
    Image::fake();

    app(Ai::class)->task('test:hero')->generate(Image::of('A lighthouse'));

    Image::assertGenerated(fn (ImagePrompt $prompt): bool => $prompt->provider->name() === 'gemini'
        && $prompt->model === 'gemini-image');
});

it('refuses to run an operation of another modality', function (): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic']]]);

    app(Ai::class)->task('test:summarize')->generate(Image::of('A lighthouse'));
})->throws(LogicException::class);

it('resolves queued prompts when the job runs', function (): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic']]]);
    Queue::fake();

    app(Ai::class)->task('test:summarize')->queue(new AnonymousAgent('Summarize.', [], []), 'Long text');

    $job = Queue::pushedJobs()[InvokeTaskAgent::class][0]['job'];
    aiSettings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
        'tasks' => ['test:summarize' => ['enabled' => false]],
    ]);

    expect(fn () => app()->call($job->handle(...)))
        ->toThrow(fn (AiUnavailableException $e) => expect($e->reason)->toBe(AiUnavailableReason::TaskDisabled));
});

it('reports each completed request with its usage', function (): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic', 'model' => 'claude-small']]]);
    AnonymousAgent::fake(['A short summary.']);
    $completed = [];
    Event::listen(TaskCompleted::class, function (TaskCompleted $event) use (&$completed): void {
        $completed[] = $event;
    });

    app(Ai::class)->task('test:summarize')->prompt(new AnonymousAgent('Summarize.', [], []), 'Long text');

    expect($completed)->toHaveCount(1)
        ->and($completed[0]->task)->toBe(SummarizeTask::class)
        ->and($completed[0]->provider->provider)->toBe('anthropic')
        ->and($completed[0]->usage)->toBeInstanceOf(TextUsage::class);
});

it('lets listeners cancel a request before it is sent, without reporting a failure', function (): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic']]]);
    AnonymousAgent::fake();
    Event::listen(TaskRunning::class, fn (TaskRunning $event) => $event->cancel = true);
    Event::listen(TaskFailed::class, fn () => throw new LogicException('A canceled request must not be reported as failed.'));

    expect(fn () => app(Ai::class)->task('test:summarize')->prompt(new AnonymousAgent('Summarize.', [], []), 'Long text'))
        ->toThrow(fn (AiUnavailableException $e) => expect($e->reason)->toBe(AiUnavailableReason::Canceled));

    AnonymousAgent::assertNeverPrompted();
});

it('reports a streamed prompt once the stream ends', function (): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic']]]);
    AnonymousAgent::fake(['A short summary.']);
    $completed = 0;
    Event::listen(TaskCompleted::class, function () use (&$completed): void {
        $completed++;
    });

    $stream = app(Ai::class)->task('test:summarize')->stream(new AnonymousAgent('Summarize.', [], []), 'Long text');

    expect($completed)->toBe(0);

    foreach ($stream as $event) {
        //
    }

    expect($completed)->toBe(1);
});

it('reports failed requests and rethrows their exception', function (string $method): void {
    registerTask();
    aiSettings(['defaults' => ['text' => ['provider' => 'anthropic']]]);
    AnonymousAgent::fake([fn () => throw new RuntimeException('Overloaded')]);
    $failed = [];
    Event::listen(TaskFailed::class, function (TaskFailed $event) use (&$failed): void {
        $failed[] = $event;
    });
    Event::listen(TaskCompleted::class, fn () => throw new LogicException('A failed request must not complete.'));

    expect(function () use ($method): void {
        $response = app(Ai::class)->task('test:summarize')->{$method}(new AnonymousAgent('Summarize.', [], []), 'Long text');

        foreach ($method === 'stream' ? $response : [] as $event) {
            //
        }
    })->toThrow(RuntimeException::class, 'Overloaded');

    expect($failed)->toHaveCount(1)
        ->and($failed[0]->task)->toBe(SummarizeTask::class)
        ->and($failed[0]->exception->getMessage())->toBe('Overloaded');
})->with(['prompt', 'stream']);
