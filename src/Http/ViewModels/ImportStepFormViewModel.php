<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;

use function CraftCms\Cms\t;

/**
 * The form shown in an import step's slideout: the importer type, the data it reads, and
 * whatever settings that importer type asks for.
 */
class ImportStepFormViewModel extends ViewModel
{
    public function __construct(
        private readonly ?BaseImporter $importer,
        private readonly Import $importService,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
        private readonly ?int $batchSize = null,
    ) {}

    public function form(): FormPayload
    {
        $hasType = $this->importer !== null;
        $mode = $this->readOnly || ! $this->canSave ? ControlMode::ReadOnly : ControlMode::Editable;
        $refreshable = ! $this->readOnly;

        $form = $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            FormField::make(t('Importer Type'), Choice::make('type')
                ->options($this->importerTypeOptions())
                ->placeholder(t('Please select'))
                ->reactive())
                ->instructions(t('What this step imports.')),
            FormField::make(t('Data File'), Text::make('file')
                ->placeholder('@root/resources/my-data.json'))
                ->instructions(t('The @root-relative path to the file containing the data you want this step to import.'))
                ->required()
                ->visible($hasType),
            FormField::make(t('Transformer'), Text::make('transformer')
                ->value($this->importer?->usesDefaultTransformer() ? null : $this->importer?->transformerAsString())
                ->placeholder(ElementTransformer::class))
                ->instructions(t('The fully qualified class name of the transformer you’d like to use.'))
                ->visible($hasType),
            FormField::make(t('Custom batch size'), Number::make('batchSize'))
                ->instructions(t('By default, this step will be run in batches containing up to 5 items. You can provide a different size, if you wish. Set to 0 to disable batching.'))
                ->visible($hasType),
        ]), new FormContext(
            values: [
                'uid' => $this->importer?->uid,
                'type' => $this->importer !== null ? $this->importer::class : null,
                'file' => $this->importer?->file,
                'batchSize' => $this->batchSize,
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
                    Group::make('import-step-settings', $settingsNodes)->dependsOn('type'),
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

    /** @return list<array{value: class-string<BaseImporter>, label: string}> */
    private function importerTypeOptions(): array
    {
        return array_map(fn (string $type): array => [
            'label' => $type::displayName(),
            'value' => $type,
        ], $this->importService->getAllImporterTypes());
    }
}
