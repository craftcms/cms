<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Import\DataTypes\DataTypeInterface;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Import\SourceSuggestions;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Nodes\Slot;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * The UI shown in an import plan step's slideout: the data it reads, what it imports and that
 * importer type's own settings, a place for its mapping overview, and its advanced settings.
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
        $mode = $this->canSave ? ControlMode::Editable : ControlMode::ReadOnly;
        $refreshable = $this->canSave;

        $resolvedDataType = $this->importer !== null && ! empty($this->importer->source)
            ? $this->importer::resolvedDataType($this->importer->source, $this->importer->dataType)
            : null;
        $dataTypeNodes = $this->dataTypeNodes($resolvedDataType);
        $settingsNodes = $this->importer?->settingsUi() ?? [];

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('uid'),
            UiField::make(t('Data Source'), Text::make('source')
                ->placeholder('resources/my-data.json')
                ->textExpanderTriggers($this->canSave ? SourceSuggestions::triggers('import/source-suggestions') : []))
                ->instructions(t('The aliased or @root-relative path to a file or a URL containing the data you want this step to import.'))
                ->tip($this->canSave ? t('Type `./` to browse the project folder, or `@` for an alias.') : null)
                ->required(),
            // shown with the source, which decides whether they apply
            ...(empty($dataTypeNodes) ? [] : [
                Scope::make('settings', $dataTypeNodes, uid: 'import-step-data-type'),
            ]),
            UiField::make(t('Importer Type'), Choice::make('type')
                ->options($this->importerTypeOptions())
                ->placeholder(t('Please select'))
                ->reactive())
                ->instructions(t('What this step imports.')),
            ...(empty($settingsNodes) ? [] : [
                Scope::make('settings', [
                    Group::make('import-step-settings', $settingsNodes)->flat()->dependsOn('type'),
                ]),
            ]),
            // the page rendering the form fills this with the step's mapping overview, which follows the
            // step's unsaved mapping without a request
            Slot::make('mapping'),
            Group::make('import-step-advanced', [
                UiField::make(t('Transformer'), Text::make('transformer')
                    ->value($this->importer?->usesDefaultTransformer() ? null : $this->importer?->transformerAsString())
                    ->placeholder(ElementTransformer::class))
                    ->instructions(t('The fully qualified class name of the transformer you’d like to use.')),
                UiField::make(t('Batch size'), Number::make('batchSize'))
                    ->instructions(t('Set to 0 to disable batching.')),
            ])->label(t('Advanced'))->collapsible(),
        ]), new UiContext(
            values: [
                'uid' => $this->importer?->uid,
                'type' => $this->importer !== null ? $this->importer::class : null,
                'source' => $this->importer?->source,
                'batchSize' => $this->batchSize ?? ImportJob::DEFAULT_BATCH_SIZE,
                'settings' => ['dataTypeSettings' => ['type' => $resolvedDataType]],
            ],
            mode: $mode,
            refreshable: $refreshable,
        ));
    }

    /**
     * Returns the fields for how the source is read: which data type to read it as, when that's open to a choice,
     * and the settings of the data type it's definitely read as.
     *
     * @param  class-string<DataTypeInterface>|null  $resolvedDataType
     * @return list<Node>
     */
    private function dataTypeNodes(?string $resolvedDataType): array
    {
        if ($this->importer === null || empty($this->importer->source)) {
            return [];
        }

        $importer = $this->importer;
        $sourceDataTypes = $importer::sourceDataTypes($importer->source);
        $nodes = [];

        if ($sourceDataTypes === null) {
            // a URL whose extension no data type claims is read as whatever its response is, unless one is chosen
            $nodes[] = UiField::make(t('Data Type'), Choice::make('dataType')
                ->options($this->dataTypeOptions($this->importService->getAllDataTypes()))
                ->placeholder(t('Detect from response'))
                ->value($resolvedDataType)
                ->reactive())
                ->instructions(t('The type of data the URL returns. Choose one to set how it’s read.'));
        } elseif (count($sourceDataTypes) > 1 || ($importer->dataType !== null && $resolvedDataType === null && $sourceDataTypes !== [])) {
            // a saved choice that the contents no longer match is shown too, so it can be changed
            $nodes[] = UiField::make(t('Data Type'), Choice::make('dataType')
                ->options($this->dataTypeOptions($sourceDataTypes))
                ->placeholder(t('Please select'))
                ->value($resolvedDataType)
                ->reactive())
                ->instructions(t('The data source could be read as more than one type of data. Choose which one it holds.'))
                ->required();
        }

        if ($resolvedDataType === null) {
            return $nodes;
        }

        $extension = strtolower(pathinfo((string) parse_url($importer->source, PHP_URL_PATH), PATHINFO_EXTENSION));
        $settingsNodes = $resolvedDataType::settingsUi(
            $importer->dataTypeSettingsFor($resolvedDataType),
            $sourceDataTypes === null ? $resolvedDataType::extensions()[0] : $extension,
        );

        if ($settingsNodes !== []) {
            // the settings are tagged with the data type they're for, so they're dropped if it changes; the tag is
            // read-only so a refresh replaces it, where an editable value would stay as the client holds it
            $nodes[] = Scope::make('dataTypeSettings', [
                HiddenField::make('type')->mode(ControlMode::ReadOnly),
                ...$settingsNodes,
            ]);
        }

        return $nodes;
    }

    /**
     * @param  list<class-string<DataTypeInterface>>  $dataTypes
     * @return list<array{value: class-string<DataTypeInterface>, label: string}>
     */
    private function dataTypeOptions(array $dataTypes): array
    {
        return array_map(fn (string $type): array => [
            'label' => $type::displayName(),
            'value' => $type,
        ], $dataTypes);
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
