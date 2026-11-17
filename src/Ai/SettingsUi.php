<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Controls\BooleanMenu;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use CraftCms\Cms\Ui\Nodes\Callout;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Ui;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Providers\AudioProvider;
use Laravel\Ai\Contracts\Providers\ClassificationProvider;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Contracts\Providers\RerankingProvider;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Providers\TranscriptionProvider;
use Throwable;

use function CraftCms\Cms\enum_value;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SettingsUi
{
    public function __construct(
        private AiTasks $tasks,
        private Plugins $plugins,
        private AiManager $aiManager,
        private Ai $ai,
    ) {}

    /**
     * Builds the settings Ui. Model options follow the providers in the form values, so the Ui refreshes when a provider changes.
     *
     * @param  array<string, mixed>|null  $values  The current form values, or `null` for the stored settings.
     */
    public function make(Settings $settings, ?array $values = null): Ui
    {
        $tasks = $this->tasks->tasksByHandle()->all();
        $values ??= $this->values($settings);

        if ($tasks === []) {
            return Ui::make([
                Heading::make('ai-tasks-heading', t('Tasks'))
                    ->description(t('Nothing uses AI yet.')),
            ]);
        }

        $nodes = [];
        $problems = $this->ai->problems();

        if ($problems !== []) {
            $nodes[] = Callout::make('ai-problems-warning', implode("\n", [
                t('{count, plural, =1{This AI task can’t run until it’s set up or disabled:} other{These AI tasks can’t run until they’re set up or disabled:}}', [
                    'count' => count($problems),
                ]),
                '',
                ...array_map(static fn (string $problem): string => "- {$problem}", array_values($problems)),
            ]))->variant('warning');
        }

        $nodes = [
            ...$nodes,
            Heading::make('ai-defaults-heading', t('Defaults'))
                ->description(t('Providers come from config/ai.php. Tasks without their own provider use the default for their modality. A modality without a default provider is off.')),
        ];

        $modalities = $settings->modalitiesInUse();

        if (in_array(Modality::Embeddings, $modalities, true)) {
            $nodes[] = Callout::make('ai-embeddings-warning', t('Changing the provider or model of an embeddings task breaks the embeddings it already created, because embeddings from different models can’t be compared. Plugins that store embeddings, such as for search, will need to re-index their content.'))
                ->variant('warning');
        }

        $defaultProviders = [];

        foreach ($modalities as $modality) {
            $defaultProviders[$modality->value] = self::stringValue($values, ['defaults', $modality->value, 'provider']);
            $nodes[] = Scope::make(['defaults', $modality->value], [
                Field::make(t('{modality} Provider', ['modality' => $modality->label()]), $this->providerControl($modality)->reactive())
                    ->width(FieldWidth::Half),
                Field::make(t('{modality} Model', ['modality' => $modality->label()]), $this->modelControl($defaultProviders[$modality->value], $modality))
                    ->width(FieldWidth::Half),
            ], "ai-defaults-{$modality->value}");
        }

        $cellOptions = [];

        foreach ($tasks as $handle => $task) {
            $provider = self::stringValue($values, ['tasks', $handle, 'provider']) ?? $defaultProviders[$task::modality()->value] ?? null;
            $cellOptions[$handle] = [
                'provider' => ['control' => $this->providerControl($task::modality())->placeholder(t('Use default'))],
                'model' => ['control' => $this->modelControl($provider, $task::modality())],
            ];
        }

        $nodes[] = Separator::make('ai-tasks-separator');
        $nodes[] = Heading::make('ai-tasks-heading', t('Tasks'))
            ->description(t('Each AI feature, in Craft or a plugin, is a task. Choose a provider and model for each one, or disable it.'));

        $nodes[] = Field::make(t('Tasks'), Table::make('tasks')
            ->keyed()
            ->columns([
                'label' => ['type' => 'heading', 'html' => true, 'heading' => t('Task'), 'class' => 'wrap'],
                'enabled' => ['heading' => t('Status'), 'width' => '15%', 'control' => BooleanMenu::make('enabled')
                    ->yesLabel(t('Enabled'))
                    ->noLabel(t('Disabled'))
                    ->includeEnvVars()],
                'provider' => ['heading' => t('Provider'), 'width' => '20%', 'control' => $this->providerControl(Modality::Text)->placeholder(t('Use default'))],
                'model' => ['heading' => t('Model'), 'width' => '20%', 'control' => $this->modelControl(null, Modality::Text)],
            ])
            ->cellOptions($cellOptions)
            ->reactive())
            ->labelSrOnly();

        return Ui::make($nodes);
    }

    /**
     * Returns the form values for the settings, with each task’s `enabled` value as a {@see BooleanMenu} option.
     *
     * @return array<string, mixed>
     */
    public function values(Settings $settings): array
    {
        $tasks = [];

        foreach ($this->tasks->tasksByHandle()->all() as $handle => $task) {
            $mapping = $settings->tasks[$handle] ?? [];
            $tasks[$handle] = [
                'label' => $this->taskLabelHtml($task),
                'enabled' => BooleanMenu::optionValue($mapping['enabled'] ?? true),
                'provider' => $mapping['provider'] ?? null,
                'model' => $mapping['model'] ?? null,
            ];
        }

        return [
            'defaults' => $settings->defaults,
            'tasks' => $tasks,
        ];
    }

    private function providerControl(Modality $modality): Combobox
    {
        $providers = array_map(function (string $handle): array {
            $driver = config("ai.providers.{$handle}.driver");
            $driver = enum_value($driver);
            $hints = [
                ...(is_string($driver) && $driver !== $handle ? [$driver] : []),
                ...($this->ai->missingKey($handle) ? [t('No API key')] : []),
            ];

            return [
                'label' => $handle,
                'value' => $handle,
                ...($hints !== [] ? ['data' => ['hint' => implode(' · ', $hints)]] : []),
            ];
        }, $this->ai->providers($modality));

        return Combobox::make('provider')
            ->options([
                ['type' => 'optgroup', 'label' => t('Providers'), 'options' => $providers],
                ...SelectOptions::getEnvSuggestions(),
            ])
            ->showAllOnEmpty()
            ->clearable();
    }

    private function modelControl(?string $provider, Modality $modality): Combobox
    {
        $models = $provider === null ? [] : $this->modelOptions($provider, $modality);

        return Combobox::make('model')
            ->options([
                ...($models === [] ? [] : [['type' => 'optgroup', 'label' => $provider, 'options' => $models]]),
                ...SelectOptions::getEnvSuggestions(),
            ])
            ->placeholder(t('Provider default'))
            ->showAllOnEmpty()
            ->clearable();
    }

    /**
     * Returns the models that `laravel/ai` names for a provider and modality, such as its default, cheapest, and smartest text models.
     *
     * @return list<array{label: string, value: string, data: array{hint: string}}>
     */
    private function modelOptions(string $provider, Modality $modality): array
    {
        $provider = Env::parse($provider);

        if (! is_string($provider) || ! array_key_exists($provider, config('ai.providers', []))) {
            return [];
        }

        try {
            $instance = $this->aiManager->instance($provider);

            $models = match (true) {
                $modality === Modality::Text && $instance instanceof TextProvider => [
                    t('Default') => $instance->defaultTextModel(),
                    t('Cheapest') => $instance->cheapestTextModel(),
                    t('Smartest') => $instance->smartestTextModel(),
                ],
                $modality === Modality::Image && $instance instanceof ImageProvider => [t('Default') => $instance->defaultImageModel()],
                $modality === Modality::Audio && $instance instanceof AudioProvider => [t('Default') => $instance->defaultAudioModel()],
                $modality === Modality::Transcription && $instance instanceof TranscriptionProvider => [t('Default') => $instance->defaultTranscriptionModel()],
                $modality === Modality::Embeddings && $instance instanceof EmbeddingProvider => [t('Default') => $instance->defaultEmbeddingsModel()],
                $modality === Modality::Reranking && $instance instanceof RerankingProvider => [t('Default') => $instance->defaultRerankingModel()],
                $modality === Modality::Classification && $instance instanceof ClassificationProvider => [t('Default') => $instance->defaultClassificationModel()],
                default => [],
            };
        } catch (Throwable) {
            // Custom drivers may fail to build without their credentials. Admins can still enter a model.
            return [];
        }

        // Providers may use one model for several roles, such as Ollama.
        $roles = [];

        foreach ($models as $role => $model) {
            $roles[$model][] = $role;
        }

        return array_map(static fn (int|string $model, array $modelRoles): array => [
            'label' => (string) $model,
            'value' => (string) $model,
            'data' => ['hint' => implode(', ', $modelRoles)],
        ], array_keys($roles), array_values($roles));
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $path
     */
    private static function stringValue(array $values, array $path): ?string
    {
        $value = data_get($values, $path);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Returns the task’s name, with its description in an info icon, and the plugin that registered it and what it needs below, as HTML.
     *
     * @param  class-string<TaskInterface>  $task
     */
    private function taskLabelHtml(string $task): string
    {
        $label = $task::label();
        $description = $task::description();
        $infoIcon = $description !== null
            ? Html::tag('craft-info-icon', Html::encode($description), [
                'label' => t('More info about {name}', ['name' => $label]),
            ])
            : '';

        $html = Html::tag('div', Html::encode($label).$infoIcon, [
            'class' => ['flex', 'items-center', 'gap-1'],
        ]);

        $pluginHandle = $this->plugins->getPluginHandleByClass($task);

        if ($pluginHandle !== null) {
            $icon = $this->plugins->hasPluginIcon($pluginHandle)
                ? Html::tag('span', Html::svg($this->plugins->getPluginIconSvg($pluginHandle), sanitize: true, namespace: true), [
                    'class' => ['inline-flex', 'size-4', 'shrink-0', '[&>svg]:size-full'],
                    'aria-hidden' => 'true',
                ])
                : '';

            $html .= Html::tag('div', $icon.Html::encode($this->plugins->getPlugin($pluginHandle)->name ?? $pluginHandle), [
                'class' => ['flex', 'items-center', 'gap-1', 'my-xs', 'text-xs', 'font-normal', 'text-quiet'],
            ]);
        }

        $requirements = array_map(static fn (Capability $capability): string => mb_strtolower($capability->label()), $task::requirements());

        if ($requirements !== []) {
            $html .= Html::tag('div', Html::encode(t('Needs {requirements}', ['requirements' => implode(', ', $requirements)])), [
                'class' => ['my-xs', 'text-xs', 'font-normal', 'text-quiet'],
            ]);
        }

        return $html;
    }
}
