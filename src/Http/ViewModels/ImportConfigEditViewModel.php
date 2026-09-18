<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Element\Import\ElementTransformer;
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
            FormField::make(t('Data File'), Text::make('file')
                ->placeholder('@root/resources/my-data.json'))
                ->instructions(t('The @root-relative path to the file containing the data you want to import.'))
                ->required()
                ->visible($hasType),
            FormField::make(t('Transformer'), Text::make('transformer')
                ->value($this->importer?->usesDefaultTransformer() ? null : $this->importer?->transformerAsString())
                ->placeholder(ElementTransformer::class))
                ->instructions(t('The fully qualified class name of the transformer you’d like to use.'))
                ->visible($hasType),

        ]), new FormContext(
            values: [
                'uid' => $this->importer?->uid,
                'type' => $this->importer !== null ? $this->importer::class : null,
                'name' => $this->importer?->name,
                'handle' => $this->importer?->handle,
                'description' => $this->importer?->description,
                'file' => $this->importer?->file,
            ],
            mode: $mode,
            refreshable: $refreshable,
        ));

        $settingsContext = new FormContext(namespace: 'settings', mode: $mode, refreshable: $refreshable);
        $settingsForm = $this->importer?->settingsForm($settingsContext);
        $settingsNodes = $settingsForm['nodes'] ?? null;
        $settingsContext = $settingsForm['context'] ?? $settingsContext;

        $settings = $this->formResolver->resolve(
            empty($settingsNodes)
                ? Form::make()
                : Form::make([
                    Group::make('import-config-settings', $settingsNodes)->dependsOn('type'),
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
        return $this->readOnly ? null : action([ImportConfigController::class, 'refreshForm']);
    }

    /** @return array<string, mixed>|null */
    public function mapping(): ?array
    {
        $mapViewModel = $this->mapViewModel();

        if ($mapViewModel === null) {
            return null;
        }

        return [
            'config' => $mapViewModel->config(),
            'destinationCols' => $mapViewModel->destinationCols(),
            'sourceDataCols' => $mapViewModel->sourceDataCols(),
            'values' => $mapViewModel->values(),
            'suggestions' => $mapViewModel->suggestions(),
            'submit' => $mapViewModel->submit(),
            'nestedColsUrl' => $mapViewModel->nestedColsUrl(),
            'readOnly' => $mapViewModel->readOnly(),
            'canSave' => $mapViewModel->canSave(),
        ];
    }

    private function mapViewModel(): ?ImportMapViewModel
    {
        $importer = $this->importer;

        if ($importer === null || $importer->uid === null) {
            return null;
        }

        if ($importer::isElementImporter() && (! $importer instanceof ElementImporter || empty($importer->fieldLayout))) {
            return null;
        }

        return new ImportMapViewModel($importer, $this->readOnly, $this->canSave);
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
