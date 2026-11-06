<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Import\DataTypes\DataTypeInterface;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Ui\Contracts\Node;
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

        $resolvedDataType = $this->importer !== null && ! empty($this->importer->source)
            ? $this->importer::resolvedDataType($this->importer->source, $this->importer->dataType)
            : null;
        $dataTypeNodes = $this->dataTypeNodes($resolvedDataType);
        $settingsNodes = $this->importer?->settingsUi() ?? [];

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('uid'),
            UiField::make(t('Importer Type'), Choice::make('type')
                ->options($this->importerTypeOptions())
                ->placeholder(t('Please select'))
                ->reactive())
                ->instructions(t('What this step imports.')),
            UiField::make(t('Data Source'), Text::make('source')
                ->placeholder('resources/my-data.json'))
                ->instructions(t('The aliased or @root-relative path to a file or a URL containing the data you want this step to import.'))
                ->required()
                ->visible($hasType),
            // shown with the source, which decides whether they apply
            ...(empty($dataTypeNodes) ? [] : [
                Scope::make('settings', $dataTypeNodes, uid: 'import-step-data-type'),
            ]),
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
