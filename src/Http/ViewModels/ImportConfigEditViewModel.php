<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Import\ImportConfigController;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;

use function CraftCms\Cms\t;

class ImportConfigEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ?BaseImporter $importer,
        private readonly Import $importService,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
    ) {}

    public function form(): FormPayload
    {
        $hasType = $this->importer !== null;
        $hasUid = $this->importer?->uid !== null;
        $mode = $this->readOnly || ! $this->canSave ? ControlMode::ReadOnly : ControlMode::Editable;
        $refreshable = ! $this->readOnly;

        $handle = Handle::make('handle');
        if (! $hasUid) {
            $handle->source('name');
        }

        $typeNode = $hasUid
            ? HiddenField::make('type')
            : FormField::make(t('Importer Type'), Choice::make('type')
                ->options($this->importerTypeOptions())
                ->placeholder(t('Please select'))
                ->reactive());

        $form = $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            $typeNode,
            FormField::make(t('Name'), Text::make('name')->autofocus())
                ->instructions(t('What this import config will be called in the control panel.'))
                ->required()
                ->visible($hasType),
            FormField::make(t('Handle'), $handle)
                ->instructions(t('How you’ll refer to this import config in the code.'))
                ->required()
                ->visible($hasType),
            FormField::make(t('Description'), Textarea::make('description'))
                ->instructions(t('A description of what this import config is for.'))
                ->visible($hasType),
        ]), new FormContext(
            values: [
                'uid' => $this->importer?->uid,
                'type' => $this->importer !== null ? $this->importer::class : null,
                'name' => $this->importer?->name,
                'handle' => $this->importer?->handle,
                'description' => $this->importer?->description,
            ],
            mode: $mode,
            refreshable: $refreshable,
        ));

        $settingsContext = new FormContext(namespace: 'settings', mode: $mode, refreshable: $refreshable);
        $settingsForm = $this->importer?->settingsForm($settingsContext);
        $settings = $this->formResolver->resolve(
            $settingsForm === null
                ? Form::make()
                : Form::make([
                    Group::make('import-config-settings', $settingsForm->nodes())->dependsOn('type'),
                ]),
            $settingsContext,
        );

        return new FormPayload(
            scope: [],
            refreshable: $refreshable,
            nodes: [...$form->nodes, ...$settings->nodes],
            values: [...$form->values, ...$settings->values],
            errors: [...$form->errors, ...$settings->errors],
            globalErrors: [...$form->globalErrors, ...$settings->globalErrors],
        );
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportConfigController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly ? null : action([ImportConfigController::class, 'renderForm']);
    }

    /** @return list<array{value: class-string<BaseImporter>, label: string}> */
    private function importerTypeOptions(): array
    {
        return array_map(fn (string $type): array => [
            'label' => $type::displayName(),
            'value' => $type,
        ], $this->importService->getAllImporterTypes());
    }
}
