<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Http\Controllers\Import\ImportPlansController;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Data\StepSummary;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportRuns;
use CraftCms\Cms\Import\Models\ImportRun;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Slot;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImportPlanEditViewModel extends ViewModel
{
    /**
     * How many runs before the latest the details sidebar lists.
     */
    private const int EARLIER_RUNS = 3;

    public function __construct(
        private readonly ImportPlanData $importPlan,
        private readonly Import $importService,
        private readonly UiResolver $uiResolver,
        private readonly bool $canSave = true,
        private readonly bool $canRun = false,
        /** @var array<string, mixed>|null */
        private readonly ?array $deleteAction = null,
    ) {}

    /**
     * The request that deletes the plan, once it's confirmed, or null when it can't be deleted.
     *
     * @return array<string, mixed>|null
     */
    public function deleteAction(): ?array
    {
        return $this->deleteAction;
    }

    /** Whether the page only shows the plan, which it does for one registered in code. */
    public function readOnly(): bool
    {
        return ! $this->importPlan->isEditable();
    }

    /** Why the page only shows the plan, for the banner across its top. */
    public function readOnlyNotice(): ?string
    {
        return $this->readOnly() ? t('This import plan is registered in code, so it can’t be edited here.') : null;
    }

    /** Whether the plan can be run from its page: one that's saved or registered, by those allowed to run plans. */
    public function canTrigger(): bool
    {
        return $this->canRun && $this->exists();
    }

    public function ui(): UiPayload
    {
        $mode = $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly;

        $handle = Handle::make('handle');
        if (! $this->importPlan->uid) {
            $handle->source('name');
        }

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('uid'),
            UiField::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this import plan will be called in the control panel.'))
                ->required(),
            UiField::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this import plan in the code.'))
                ->required(),
            UiField::make(t('Description'), Textarea::make('description'))
                ->instructions(t('A description of what this import plan is for.')),
            UiField::make(t('Steps'), Table::make('steps'))
                ->instructions(t('Each step imports one kind of data. Steps run in the order listed here.')),
            // the page fills this with what running the steps as the form has them would do
            Slot::make('preview'),
        ]), new UiContext(
            values: [
                'uid' => $this->importPlan->uid,
                'name' => $this->importPlan->name,
                'handle' => $this->importPlan->handle,
                'description' => $this->importPlan->description,
                'steps' => $this->importPlan->serializeSteps() ?? [],
            ],
            mode: $mode,
        ));
    }

    /**
     * The importer types a step can be, for the step slideout's type select.
     *
     * @return list<array{value: class-string<BaseImporter>, label: string}>
     */
    public function importerTypes(): array
    {
        return array_map(fn (string $type): array => [
            'label' => $type::displayName(),
            'value' => $type,
        ], $this->importService->getAllImporterTypes());
    }

    /**
     * Where the plan's latest run has got, overall and step by step; the page follows it from here.
     *
     * @return array<string, mixed>|null
     */
    public function runState(): ?array
    {
        return $this->exists() ? $this->importService->runState($this->importPlan) : null;
    }

    /**
     * The few runs before the latest, newest first, for the details sidebar.
     *
     * @return list<array<string, mixed>>
     */
    public function earlierRuns(): array
    {
        if (! $this->exists()) {
            return [];
        }

        $importRuns = app(ImportRuns::class);

        return $importRuns->forPlan($this->importPlan)
            ->with('user')
            ->latest('dateCreated')
            ->latest('id')
            ->skip(1)
            ->take(self::EARLIER_RUNS)
            ->get()
            ->map(fn (ImportRun $run): array => $importRuns->toArray($run, withFailures: false))
            ->values()
            ->all();
    }

    /**
     * How many runs the plan has kept, and where they're all listed.
     *
     * @return array{count: int, url: string}|null
     */
    public function runs(): ?array
    {
        if (! $this->exists() || ! $this->importPlan->handle) {
            return null;
        }

        return [
            'count' => app(ImportRuns::class)->forPlan($this->importPlan)->count(),
            'url' => route('craft.cp.import.activity.index', ['plan' => $this->importPlan->handle]),
        ];
    }

    /**
     * Each saved step's mapping page, by its uid. A step yet to be saved has none.
     *
     * @return array<string, string>
     */
    public function stepMappingUrls(): array
    {
        if (! $this->importPlan->uid || ! $this->importPlan->handle) {
            return [];
        }

        return collect($this->importPlan->serializeSteps() ?? [])
            ->mapWithKeys(fn (array $step) => [
                $step['uid'] => route('craft.cp.import.steps.mapping', ['handle' => $this->importPlan->handle, 'uid' => $step['uid']]),
            ])
            ->all();
    }

    /**
     * Each saved step's own page, by its uid. A step yet to be saved has none.
     *
     * @return array<string, string>
     */
    public function stepEditUrls(): array
    {
        if (! $this->importPlan->uid || ! $this->importPlan->handle) {
            return [];
        }

        return collect($this->importPlan->serializeSteps() ?? [])
            ->mapWithKeys(fn (array $step) => [
                $step['uid'] => route('craft.cp.import.steps.edit', ['handle' => $this->importPlan->handle, 'uid' => $step['uid']]),
            ])
            ->all();
    }

    /**
     * Each step at a glance, by its uid, for the list of steps.
     *
     * @return array<string, StepSummary>
     */
    public function stepSummaries(): array
    {
        return StepSummary::forPlan($this->importPlan);
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportPlansController::class, 'store']),
        ];
    }

    /** Whether the plan has runs to show: it's been saved, or it's registered in code. */
    private function exists(): bool
    {
        return $this->importPlan->uid !== null || ! $this->importPlan->isEditable();
    }
}
