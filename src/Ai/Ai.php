<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\AiUnavailableReason;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Support\Env;
use Illuminate\Container\Attributes\Singleton;

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
    public function __construct(
        private readonly AiTasks $tasks,
    ) {}

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
}
