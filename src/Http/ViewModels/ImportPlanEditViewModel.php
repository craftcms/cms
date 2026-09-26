<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Import\ImportPlansController;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;

use function CraftCms\Cms\t;

class ImportPlanEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportPlanData $importPlan,
        private readonly Import $importService,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
    ) {}

    public function form(): FormPayload
    {
        $mode = $this->readOnly || ! $this->canSave ? ControlMode::ReadOnly : ControlMode::Editable;

        $handle = Handle::make('handle');
        if (! $this->importPlan->uid) {
            $handle->source('name');
        }

        return $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            FormField::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this import plan will be called in the control panel.'))
                ->required(),
            FormField::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this import plan in the code.'))
                ->required(),
            FormField::make(t('Description'), Textarea::make('description'))
                ->instructions(t('A description of what this import plan is for.')),
        ]), new FormContext(
            values: [
                'uid' => $this->importPlan->uid,
                'name' => $this->importPlan->name,
                'handle' => $this->importPlan->handle,
                'description' => $this->importPlan->description,
            ],
            mode: $mode,
        ));
    }

    /**
     * The import plan's steps, as the step list on the edit screen holds them.
     *
     * @return list<array<string, mixed>>
     */
    public function steps(): array
    {
        return $this->importPlan->serializeSteps() ?? [];
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

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportPlansController::class, 'store']),
        ];
    }

    public function stepSettingsUrl(): ?string
    {
        return $this->readOnly ? null : action([ImportPlansController::class, 'stepSettings']);
    }

    public function validateStepUrl(): ?string
    {
        return $this->readOnly ? null : action([ImportPlansController::class, 'validateStep']);
    }

    public function stepMappingUrl(): string
    {
        return action([ImportPlansController::class, 'stepMapping']);
    }

    public function nestedColsUrl(): string
    {
        return action([ImportPlansController::class, 'nestedMappingCols']);
    }

    public function readOnly(): bool
    {
        return $this->readOnly;
    }

    public function canSave(): bool
    {
        return $this->canSave;
    }
}
