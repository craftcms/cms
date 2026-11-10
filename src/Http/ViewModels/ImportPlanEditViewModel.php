<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Http\Controllers\Import\ImportPlansController;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Data\StepSummary;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\ImportPlans;
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
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImportPlanEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportPlanData $importPlan,
        private readonly Import $importService,
        private readonly UiResolver $uiResolver,
        private readonly bool $canSave = true,
    ) {}

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
        $summaries = [];

        foreach ($this->importPlan->serializeSteps() ?? [] as $step) {
            try {
                $summaries[$step['uid']] = StepSummary::fromImporter(ImportPlans::createImporter($step));
            } catch (Throwable) {
                // a step that can't be built is summed up from what it holds itself
            }
        }

        return $summaries;
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportPlansController::class, 'store']),
        ];
    }
}
