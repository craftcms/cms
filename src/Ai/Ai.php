<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use BackedEnum;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\AiUnavailableReason;
use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Support\Env;
use Illuminate\Container\Attributes\Singleton;
use Laravel\Ai\AiManager;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Resolves AI tasks to the providers and models that admins choose.
 *
 * Providers are the entries in the `ai.providers` config. Craft never falls back
 * to `ai.default`, so a task is only available once Craft’s AI settings
 * select a provider for it, or for its modality.
 *
 * ```php
 * use CraftCms\Cms\Support\Facades\Ai;
 *
 * if (Ai::available('altText:describe')) {
 *     $response = Ai::task('altText:describe')->prompt(agent: new AltTextAgent, prompt: 'Describe this image');
 * }
 * ```
 *
 * @since 6.0.0
 */
#[Singleton]
class Ai
{
    /**
     * `laravel/ai` drivers that can work without an API key, such as Ollama, Bedrock with AWS credentials,
     * and local OpenAI-compatible servers.
     */
    private const array KEYLESS_DRIVERS = ['bedrock', 'ollama', 'openai-compatible'];

    public function __construct(
        private readonly AiTasks $tasks,
    ) {}

    /**
     * Returns the handles of the `ai.providers` entries that support a modality.
     *
     * Providers whose driver can’t be built, such as custom drivers that need credentials, are included,
     * because Craft can’t tell what they support.
     *
     * @return list<string>
     */
    public function providers(Modality $modality): array
    {
        $contract = $modality->providerContract();

        return array_values(array_filter(
            array_map(strval(...), array_keys(config('ai.providers', []))),
            static function (string $handle) use ($contract): bool {
                try {
                    return app(AiManager::class)->instance($handle) instanceof $contract;
                } catch (Throwable) {
                    return true;
                }
            },
        ));
    }

    /**
     * Returns whether a provider is missing its API key: its config has an empty `key`, and its driver needs one.
     *
     * This is a hint for admins, not a guarantee. Craft can’t tell whether a custom driver needs a key,
     * so it’s only reported missing when the provider’s config has an empty `key`.
     */
    public function missingKey(string $provider): bool
    {
        $config = config("ai.providers.{$provider}");

        if (! is_array($config) || ! array_key_exists('key', $config)) {
            return false;
        }

        $driver = $config['driver'] ?? null;
        $driver = $driver instanceof BackedEnum ? $driver->value : $driver;

        if (in_array($driver, self::KEYLESS_DRIVERS, true)) {
            return false;
        }

        return $config['key'] === null || $config['key'] === '';
    }

    /**
     * Returns why enabled tasks can’t run, indexed by task handle, for admins to fix.
     *
     * Tasks whose provider is missing its API key are included, because their requests will fail. Tasks without a
     * provider are only included once a provider has been chosen for something: until then, AI is off, which isn’t a
     * problem.
     *
     * @return array<string, string>
     */
    public function problems(): array
    {
        $isSetUp = $this->hasChosenProvider();
        $problems = [];

        foreach ($this->tasks->tasksByHandle()->all() as $handle => $task) {
            try {
                $resolved = $this->resolve($handle);
            } catch (AiUnavailableException $e) {
                $problem = match ($e->reason) {
                    AiUnavailableReason::NoDefault => ! $isSetUp ? null : t('{task} doesn’t have a provider, and there’s no default {modality} provider.', [
                        'task' => $task::label(),
                        'modality' => mb_strtolower($task::modality()->label()),
                    ]),
                    AiUnavailableReason::UnknownProvider => t('{task} uses the {provider} provider, which isn’t defined in {file}.', [
                        'task' => $task::label(),
                        'provider' => $e->provider,
                        'file' => 'config/ai.php',
                    ]),
                    default => null,
                };

                if ($problem !== null) {
                    $problems[$handle] = $problem;
                }

                continue;
            }

            if ($this->missingKey($resolved->provider)) {
                $problems[$handle] = t('{task} uses the {provider} provider, which doesn’t have an API key.', [
                    'task' => $task::label(),
                    'provider' => $resolved->provider,
                ]);
            }
        }

        return $problems;
    }

    /**
     * Returns whether a task resolves to a provider. Use it to hide AI features that can’t run.
     */
    public function available(string $handle): bool
    {
        try {
            $this->resolve($handle);
        } catch (AiUnavailableException) {
            return false;
        }

        return true;
    }

    /**
     * Returns the provider and model a task uses.
     *
     * A task uses its own provider and model when it has a provider. Otherwise it uses
     * the default provider for its modality, with its own model or the default model.
     * A `null` model means the provider’s default model.
     *
     * @throws AiUnavailableException
     */
    public function resolve(string $handle): ResolvedProvider
    {
        $task = $this->tasks->getTaskByHandle($handle)
            ?? throw new AiUnavailableException($handle, AiUnavailableReason::UnknownTask);

        $settings = app(Settings::class);
        $mapping = $settings->tasks[$handle] ?? [];

        if (Env::parseBoolean($mapping['enabled'] ?? true) === false) {
            throw new AiUnavailableException($handle, AiUnavailableReason::TaskDisabled);
        }

        $provider = Env::parse($mapping['provider'] ?? null) ?: null;
        $model = Env::parse($mapping['model'] ?? null) ?: null;

        if ($provider === null) {
            $default = $settings->defaults[$task::modality()->value] ?? [];
            $provider = Env::parse($default['provider'] ?? null) ?: null;
            $model ??= Env::parse($default['model'] ?? null) ?: null;
        }

        if ($provider === null) {
            throw new AiUnavailableException($handle, AiUnavailableReason::NoDefault);
        }

        if (! array_key_exists($provider, config('ai.providers', []))) {
            throw new AiUnavailableException($handle, AiUnavailableReason::UnknownProvider, $provider);
        }

        return new ResolvedProvider($provider, $model);
    }

    /**
     * Returns a client that runs `laravel/ai` operations with a task’s provider and model.
     *
     * @throws AiUnavailableException if the task isn’t registered
     */
    public function task(string $handle): TaskClient
    {
        $task = $this->tasks->getTaskByHandle($handle)
            ?? throw new AiUnavailableException($handle, AiUnavailableReason::UnknownTask);

        return new TaskClient($this, $task);
    }

    /**
     * Returns whether a default or a task has a provider.
     */
    private function hasChosenProvider(): bool
    {
        $settings = app(Settings::class);

        return array_any(
            [...array_values($settings->defaults), ...array_values($settings->tasks)],
            static fn (array $mapping): bool => (bool) Env::parse($mapping['provider'] ?? null),
        );
    }
}
