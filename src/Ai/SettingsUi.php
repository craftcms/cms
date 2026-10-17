<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Plugin\Plugins;
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

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SettingsUi
{
    public function __construct(
        private AiTasks $tasks,
        private Plugins $plugins,
    ) {}

    public function make(Settings $settings): Ui
    {
        $tasks = $this->tasks->tasksByHandle()->all();

        if ($tasks === []) {
            return Ui::make([
                Heading::make('ai-tasks-heading', t('Tasks'))
                    ->description(t('No installed plugins use AI yet.')),
            ]);
        }

        $nodes = [
            Heading::make('ai-defaults-heading', t('Defaults'))
                ->description(t('Providers come from config/ai.php. Tasks without their own provider use the default for their modality. A modality without a default provider is off.')),
        ];

        $modalities = $settings->modalitiesInUse();

        if (in_array(Modality::Embeddings, $modalities, true)) {
            $nodes[] = Callout::make('ai-embeddings-warning', t('Changing the provider or model of an embeddings task breaks the embeddings it already created, because embeddings from different models can’t be compared. Plugins that store embeddings, such as for search, will need to re-index their content.'))
                ->variant('warning');
        }

        foreach ($modalities as $modality) {
            $nodes[] = Scope::make(['defaults', $modality->value], [
                Field::make(t('{modality} Provider', ['modality' => $modality->label()]), $this->providerControl())
                    ->width(FieldWidth::Half),
                Field::make(t('{modality} Model', ['modality' => $modality->label()]), $this->modelControl())
                    ->width(FieldWidth::Half),
            ], "ai-defaults-{$modality->value}");
        }

        $nodes[] = Separator::make('ai-tasks-separator');
        $nodes[] = Heading::make('ai-tasks-heading', t('Tasks'))
            ->description(t('Plugins register tasks for their AI features. Choose a provider and model for each one, or disable it.'));

        $nodes[] = Field::make(t('Tasks'), Table::make('tasks')
            ->keyed()
            ->columns([
                'label' => ['type' => 'heading', 'heading' => t('Task'), 'width' => '15%'],
                'details' => ['type' => 'html', 'heading' => t('Details')],
                'enabled' => ['heading' => t('Status'), 'width' => '15%', 'control' => BooleanMenu::make('enabled')
                    ->yesLabel(t('Enabled'))
                    ->noLabel(t('Disabled'))
                    ->includeEnvVars()],
                'provider' => ['heading' => t('Provider'), 'width' => '20%', 'control' => $this->providerControl()->placeholder(t('Use default'))],
                'model' => ['heading' => t('Model'), 'width' => '20%', 'control' => $this->modelControl()],
            ]))
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
                'label' => $task::label(),
                'details' => Html::encode($this->taskDescription($task) ?? ''),
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

    private function providerControl(): Combobox
    {
        $providers = array_map(static fn (int|string $handle): array => [
            'label' => (string) $handle,
            'value' => (string) $handle,
        ], array_keys(config('ai.providers', [])));

        return Combobox::make('provider')
            ->options([
                ['type' => 'optgroup', 'label' => t('Providers'), 'options' => $providers],
                ...SelectOptions::getEnvSuggestions(),
            ])
            ->showAllOnEmpty()
            ->clearable();
    }

    private function modelControl(): Combobox
    {
        return Combobox::make('model')
            ->options(SelectOptions::getEnvSuggestions())
            ->placeholder(t('Provider default'))
            ->showAllOnEmpty()
            ->clearable();
    }

    /**
     * @param  class-string<TaskInterface>  $task
     */
    private function taskDescription(string $task): ?string
    {
        $parts = [];

        $description = $task::description();
        $pluginHandle = $this->plugins->getPluginHandleByClass($task);
        $capabilities = $task::capabilities();

        if ($description !== null) {
            $parts[] = $description;
        }

        if ($pluginHandle !== null) {
            $parts[] = $this->plugins->getPlugin($pluginHandle)->name ?? $pluginHandle;
        }

        if ($capabilities !== []) {
            $parts[] = t('Needs: {capabilities}', [
                'capabilities' => implode(', ', array_map(
                    static fn (Capability $capability): string => $capability->label(),
                    $capabilities,
                )),
            ]);
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
