<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * The UI shown in an import plan step's slideout: the importer type, the data it reads, and
 * whatever settings that importer type asks for.
 *
 * @since 6.0.0
 */
class ImportPlanStepUiViewModel extends ViewModel
{
    public function __construct(
        private readonly ?BaseImporter $importer,
        private readonly Import $importService,
        private readonly UiResolver $uiResolver,
        private readonly bool $canSave = true,
        private readonly ?int $batchSize = null,
    ) {}

    public function ui(): UiPayload
    {
        $hasType = $this->importer !== null;
        $mode = $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly;
        $refreshable = $this->canSave;

        $settingsNodes = $this->importer?->settingsUi() ?? [];

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('uid'),
            UiField::make(t('Importer Type'), Choice::make('type')
                ->options($this->importerTypeOptions())
                ->placeholder(t('Please select'))
                ->reactive())
                ->instructions(t('What this step imports.')),
            UiField::make(t('Data Source'), Text::make('source')
                ->placeholder('@root/resources/my-data.json'))
                ->instructions(t('The aliased or @root-relative path to a file or a URL containing the data you want this step to import.'))
                ->required()
                ->visible($hasType),
            UiField::make(t('Transformer'), Text::make('transformer')
                ->value($this->importer?->usesDefaultTransformer() ? null : $this->importer?->transformerAsString())
                ->placeholder(ElementTransformer::class))
                ->instructions(t('The fully qualified class name of the transformer you’d like to use.'))
                ->visible($hasType),
            UiField::make(t('Custom batch size'), Number::make('batchSize'))
                ->instructions(t('By default, this step will be run in batches containing up to 100 items. You can provide a different size, if you wish. Set to 0 to disable batching.'))
                ->visible($hasType),
            ...(empty($settingsNodes) ? [] : [
                Scope::make('settings', [
                    Group::make('import-step-settings', $settingsNodes)->dependsOn('type'),
                ]),
            ]),
        ]), new UiContext(
            values: [
                'uid' => $this->importer?->uid,
                'type' => $this->importer !== null ? $this->importer::class : null,
                'source' => $this->importer?->source,
                'batchSize' => $this->batchSize,
            ],
            mode: $mode,
            refreshable: $refreshable,
        ));
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
