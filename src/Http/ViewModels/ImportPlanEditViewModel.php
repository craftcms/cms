<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Http\Controllers\Import\ImportPlansController;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Support\Facades\Crypt;

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
        private readonly bool $canRun = false,
    ) {}

    /**
     * The fields that name and describe a plan, which a new plan's wizard asks for first.
     *
     * @return list<UiField>
     */
    public static function detailFields(bool $isNew): array
    {
        $handle = Handle::make('handle');
        if ($isNew) {
            $handle->source('name');
        }

        return [
            UiField::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this import plan will be called in the control panel.'))
                ->required(),
            UiField::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this import plan in the code.'))
                ->required(),
            UiField::make(t('Description'), Textarea::make('description'))
                ->instructions(t('A description of what this import plan is for.')),
        ];
    }

    public function ui(): UiPayload
    {
        $mode = $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly;

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('uid'),
            ...self::detailFields(! $this->importPlan->uid),
            UiField::make(t('Steps'), Table::make('steps'))
                ->instructions(t('Each step imports one kind of data. Steps run in the order listed here.'))
                ->fieldset(),
            // rendered by the page from the steps as they stand; saving the plan ignores it
            UiField::make(t('Review'), Table::make('review'))
                ->instructions(t('This is what running this import plan would do with the first rows of each step’s data. Nothing has been imported yet.'))
                ->fieldset(),
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
     * The importer types a step can be, for labeling the step cards.
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
     * Where each of the plan's saved steps has its own page, by uid.
     *
     * @return array<string, string>
     */
    public function stepPageUrls(): array
    {
        if (! $this->importPlan->uid) {
            return [];
        }

        $urls = [];

        foreach ($this->importPlan->steps ?? [] as $step) {
            if ($step->uid) {
                $urls[$step->uid] = route('craft.cp.import.steps.edit', ['handle' => $this->importPlan->handle, 'uid' => $step->uid]);
            }
        }

        return $urls;
    }

    /**
     * Running the saved plan, for its Run import button. Unset for a plan that's yet to be
     * saved, or a user who can't run it.
     *
     * @return array{url: string, uid: string, redirect: string}|null
     */
    public function run(): ?array
    {
        if (! $this->canRun || ! $this->importPlan->uid) {
            return null;
        }

        return [
            'url' => action([ImportPlansController::class, 'run']),
            'uid' => $this->importPlan->uid,
            'redirect' => Crypt::encrypt(route('craft.cp.import.index')),
        ];
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
