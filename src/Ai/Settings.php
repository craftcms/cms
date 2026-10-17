<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Craft’s AI settings, stored in project config.
 *
 * Provider and model values may reference environment variables, such as `$AI_TEXT_PROVIDER`.
 *
 * @since 6.0.0
 */
class Settings extends Component
{
    /**
     * Default providers and models, indexed by {@see Modality} value.
     *
     * @var array<string, array{provider?: string|null, model?: string|null}>
     */
    public array $defaults = [];

    /**
     * Task mappings, indexed by task handle.
     *
     * Mappings for tasks that are no longer registered are kept as they are.
     *
     * @var array<string, array{enabled?: bool|string|null, provider?: string|null, model?: string|null}>
     */
    public array $tasks = [];

    /** @return array<string, mixed> */
    public function getRules(): array
    {
        $rules = [
            'defaults' => ['array'],
            'defaults.*' => ['array'],
            'defaults.*.provider' => ['nullable', 'string', 'max:255'],
            'defaults.*.model' => ['nullable', 'string', 'max:255'],
            'tasks' => ['array'],
            'tasks.*' => ['array'],
            'tasks.*.enabled' => new EnvValueRule(['nullable', 'boolean']),
            'tasks.*.provider' => ['nullable', 'string', 'max:255'],
            'tasks.*.model' => ['nullable', 'string', 'max:255'],
        ];

        // Only validate providers the form shows, so stale mappings can’t block saving.
        foreach ($this->modalitiesInUse() as $modality) {
            $rules["defaults.{$modality->value}.provider"] = $this->providerRules();
        }

        foreach (array_keys(app(AiTasks::class)->tasksByHandle()->all()) as $handle) {
            $rules["tasks.{$handle}.provider"] = $this->providerRules();
        }

        return $rules;
    }

    public function afterValidate(?Validator $validator = null): void
    {
        foreach (array_keys($this->defaults) as $modality) {
            if (Modality::tryFrom($modality) === null) {
                $this->errors()->add('defaults', "Unknown AI modality [{$modality}].");
            }
        }
    }

    /**
     * Returns the modalities that registered tasks use.
     *
     * @return list<Modality>
     */
    public function modalitiesInUse(): array
    {
        $modalities = array_map(
            static fn (string $task): Modality => $task::modality(),
            array_values(app(AiTasks::class)->tasksByHandle()->all()),
        );

        return array_values(array_filter(
            Modality::cases(),
            static fn (Modality $modality): bool => in_array($modality, $modalities, true),
        ));
    }

    /**
     * Returns the settings for storage in project config, with empty values as `null`.
     *
     * @return array{defaults: array<string, array{provider: string|null, model: string|null}>, tasks: array<string, array{enabled: bool|string, provider: string|null, model: string|null}>}
     */
    public function toConfig(): array
    {
        return [
            'defaults' => array_map(static fn (array $default): array => [
                'provider' => self::nullIfEmpty($default['provider'] ?? null),
                'model' => self::nullIfEmpty($default['model'] ?? null),
            ], $this->defaults),
            'tasks' => array_map(static fn (array $mapping): array => [
                'enabled' => self::normalizeEnabled($mapping['enabled'] ?? true),
                'provider' => self::nullIfEmpty($mapping['provider'] ?? null),
                'model' => self::nullIfEmpty($mapping['model'] ?? null),
            ], $this->tasks),
        ];
    }

    /** @return list<mixed> */
    private function providerRules(): array
    {
        return [new EnvValueRule([
            'nullable',
            'string',
            Rule::in(array_keys(config('ai.providers', []))),
        ], showParsedValueInErrors: true)];
    }

    private static function nullIfEmpty(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }

    private static function normalizeEnabled(bool|string|null $value): bool|string
    {
        if (is_string($value) && str_starts_with($value, '$')) {
            return $value;
        }

        return Env::normalizeBooleanValue($value) ?? true;
    }
}
