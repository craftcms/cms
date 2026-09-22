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
use CraftCms\Cms\Http\Controllers\Import\ImportController;
use CraftCms\Cms\Import\Data\Import as ImportData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;

use function CraftCms\Cms\t;

class ImportEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportData $import,
        private readonly Import $importService,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
    ) {}

    public function form(): FormPayload
    {
        $mode = $this->readOnly || ! $this->canSave ? ControlMode::ReadOnly : ControlMode::Editable;

        $handle = Handle::make('handle');
        if (! $this->import->uid) {
            $handle->source('name');
        }

        return $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            FormField::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this import will be called in the control panel.'))
                ->required(),
            FormField::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this import in the code.'))
                ->required(),
            FormField::make(t('Description'), Textarea::make('description'))
                ->instructions(t('A description of what this import is for.')),
        ]), new FormContext(
            values: [
                'uid' => $this->import->uid,
                'name' => $this->import->name,
                'handle' => $this->import->handle,
                'description' => $this->import->description,
            ],
            mode: $mode,
        ));
    }

    /**
     * The import's steps, as the step list on the edit screen holds them.
     *
     * @return list<array<string, mixed>>
     */
    public function steps(): array
    {
        return $this->import->serializeSteps() ?? [];
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
            'url' => action([ImportController::class, 'store']),
        ];
    }

    public function stepSettingsUrl(): ?string
    {
        return $this->readOnly ? null : action([ImportController::class, 'stepSettings']);
    }

    public function validateStepUrl(): ?string
    {
        return $this->readOnly ? null : action([ImportController::class, 'validateStep']);
    }

    public function stepMappingUrl(): string
    {
        return action([ImportController::class, 'stepMapping']);
    }

    public function nestedColsUrl(): string
    {
        return action([ImportController::class, 'nestedMappingCols']);
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
