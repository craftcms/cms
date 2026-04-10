<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\ImportableElementContainerFieldInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Import\DataTypes\Csv;
use CraftCms\Cms\Import\DataTypes\Json;
use CraftCms\Cms\Import\DataTypes\Xml;
use CraftCms\Cms\Import\Events\DataImported;
use CraftCms\Cms\Import\Events\DataImporting;
use CraftCms\Cms\Import\Events\ImportConfigSaved;
use CraftCms\Cms\Import\Events\ImportConfigSaving;
use CraftCms\Cms\Import\Events\ImportRunDispatched;
use CraftCms\Cms\Import\Events\ImportRunDispatching;
use CraftCms\Cms\Import\Events\ImportRunSaved;
use CraftCms\Cms\Import\Events\ImportRunSaving;
use CraftCms\Cms\Import\Events\RegisterDataTypes;
use CraftCms\Cms\Import\Events\RegisterImporterTypes;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\Import\Models\ImportConfig as ImportConfigModel;
use CraftCms\Cms\Import\Models\ImportRun as ImportRunModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Json as JsonSupport;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;
use Throwable;

#[Singleton]
class Import
{
    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private ?LaravelCollection $configs = null,
        private ?LaravelCollection $runs = null,
    ) {}

    public function getAllDataTypes(): array
    {
        $dataTypes = [
            'json' => Json::class,
            'csv' => Csv::class,
            'xml' => Xml::class,
        ];

        if (Event::hasListeners(RegisterDataTypes::class)) {
            Event::dispatch($event = new RegisterDataTypes($dataTypes));

            $dataTypes = $event->dataTypes;
        }

        return $dataTypes;
    }

    public function getAllImporterTypes(): array
    {
        $importers = [
            ElementImporter::class,
        ];

        if (Event::hasListeners(RegisterImporterTypes::class)) {
            Event::dispatch($event = new RegisterImporterTypes($importers));

            $importers = $event->importers;
        }

        return $importers;
    }

    // //// configs //////
    public function createImporter($config)
    {
        $importer = new $config['type']($config);
        $importer->name($config['name']);
        $importer->handle($config['handle']);
        $importer->description($config['description']);
        $settings = JsonSupport::decode($config['settings']);
        foreach ($settings as $setting => $value) {
            if (method_exists($importer, $setting)) {
                $importer->{$setting}($value);
            }
        }

        return $importer;
    }

    public function getAllConfigs(): LaravelCollection
    {
        if ($this->configs === null) {
            $dbConfigs = $this->_importConfigQuery()->get()->all();
            $dbConfigs = array_map(
                fn ($config) => $this->createImporter((array) $config + ['editable' => true]),
                $dbConfigs
            );

            $fileConfigs = Config::get('craft.import') ?? [];

            foreach ($fileConfigs as &$fileConfig) {
                $fileConfig = $fileConfig();

                // TODO: this might be wrong - think about it;
                // if there's no transformer set, use the default one
                if ($fileConfig->transformer === null) {
                    $fileConfig->transformer(null);
                }
            }

            $this->configs = new LaravelCollection($dbConfigs + $fileConfigs)
                ->keyBy(fn (BaseImporter $item, $key) => $item->handle ?? $key)
                ->sortBy('name');
        }

        return $this->configs;
    }

    public function getEditableConfigs(): LaravelCollection
    {
        return $this->getAllConfigs()->filter(fn ($config) => $config->isEditable());
    }

    public function getNonEditableConfigs(): LaravelCollection
    {
        return $this->getAllConfigs()->reject(fn ($config) => $config->isEditable());
    }

    public function getConfigByHandle(?string $handle, bool $editableOnly = false): ?BaseImporter
    {
        if (is_null($handle)) {
            return null;
        }

        if ($editableOnly) {
            $configs = $this->getEditableConfigs();
        } else {
            $configs = $this->getAllConfigs();
        }

        /** @var BaseImporter|null */
        return $configs->where('handle', $handle)->first();
    }

    public function getConfigByUid(string $uid, bool $editableOnly = false): ?BaseImporter
    {
        if ($editableOnly) {
            $configs = $this->getEditableConfigs();
        } else {
            $configs = $this->getAllConfigs();
        }

        /** @var BaseImporter|null */
        return $configs->where('uid', $uid)->first();
    }

    public function saveConfig(BaseImporter $import): bool
    {
        $isNewConfig = ! $import->uid;

        event($event = new ImportConfigSaving($import, $isNewConfig));

        if (! $event->isValid) {
            return false;
        }

        $import = $event->import;

        if ($isNewConfig) {
            $import->uid = Str::uuid7()->toString();
        }

        $configRecord = $this->_getImportConfigModel($import->uid);

        DB::beginTransaction();

        try {
            $configRecord->uid = $import->uid;
            $configRecord->type = $import::class;
            $configRecord->name = $import->name;
            $configRecord->handle = $import->handle;
            $configRecord->description = $import->description;
            $settings = [
                'file' => $import->file,
                'site' => $import->site->uid,
                'className' => $import->className,
                'transformer' => $import->transformer ? $import->transformer::class : null,
                'map' => $import->map,
                'matchCriteria' => $import->matchCriteria,
            ];
            $configRecord->settings = $settings;
            $configRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->configs = null;

        event(new ImportConfigSaved($import, $isNewConfig));

        return true;
    }

    public function deleteConfig(BaseImporter $import): void
    {
        $configRecord = $this->_getImportConfigModel($import->uid);

        if (! $configRecord->exists) {
            return;
        }

        DB::beginTransaction();

        try {
            DB::table(Table::IMPORT_CONFIGS)->softDelete($configRecord->id);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // invalidate caches
        $this->configs = null;
    }

    /**
     * Returns an import config model for a given UID
     */
    private function _getImportConfigModel(string $uid, bool $withTrashed = false): ImportConfigModel
    {
        return ImportConfigModel::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new ImportConfigModel;
    }

    private function _importConfigQuery(): Builder
    {
        return DB::table(Table::IMPORT_CONFIGS)
            ->select([
                'import_configs.type',
                'import_configs.name',
                'import_configs.handle',
                'import_configs.description',
                'import_configs.settings',
                'import_configs.uid',
            ])
            ->orderBy('import_configs.name')
            ->orderBy('import_configs.handle')
            ->whereNull('import_configs.dateDeleted');
    }

    // //// runs //////
    public function getImportRuns(): LaravelCollection
    {
        if ($this->runs === null) {
            $runs = $this->_importRunQuery()->get()->all();
            $runs = array_map(fn ($run) => new ImportRun($run), $runs);

            $this->runs = new LaravelCollection($runs)->keyBy('handle')->sortBy('name');
        }

        return $this->runs;
    }

    public function getImportRunByHandle(?string $handle): ?ImportRun
    {
        if (is_null($handle)) {
            return null;
        }

        /** @var ImportRun|null */
        return $this->getImportRuns()->where('handle', $handle)->first();
    }

    public function getImportRunByUid(string $uid): ?ImportRun
    {
        /** @var ImportRun|null */
        return $this->getImportRuns()->where('uid', $uid)->first();
    }

    public function saveRun(ImportRun $run): bool
    {
        $isNewRun = ! $run->uid;

        event($event = new ImportRunSaving($run, $isNewRun));

        if (! $event->isValid) {
            return false;
        }

        $run = $event->run;

        if (! $run->validate()) {
            return false;
        }

        if ($isNewRun) {
            $run->uid = Str::uuid7()->toString();
        }

        $runRecord = $this->_getImportRunModel($run->uid);

        DB::beginTransaction();

        try {
            $runRecord->uid = $run->uid;
            $runRecord->name = $run->name;
            $runRecord->handle = $run->handle;
            $runRecord->description = $run->description;
            $runRecord->steps = $run->steps;

            $runRecord->save();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        // invalidate caches
        $this->runs = null;

        event(new ImportRunSaved($run, $isNewRun));

        return true;
    }

    public function deleteRun(ImportRun $run): void
    {
        $runRecord = $this->_getImportRunModel($run->uid);

        if (! $runRecord->exists) {
            return;
        }

        DB::beginTransaction();

        try {
            DB::table(Table::IMPORT_RUNS)->softDelete($runRecord->id);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // invalidate caches
        $this->runs = null;
    }

    /**
     * Returns an import config model for a given UID
     */
    private function _getImportRunModel(string $uid, bool $withTrashed = false): ImportRunModel
    {
        return ImportRunModel::withTrashed($withTrashed)
            ->where('uid', $uid)
            ->first() ?? new ImportRunModel;
    }

    private function _importRunQuery(): Builder
    {
        return DB::table(Table::IMPORT_RUNS)
            ->select([
                'import_runs.name',
                'import_runs.handle',
                'import_runs.description',
                'import_runs.steps',
                'import_runs.uid',
            ])
            ->orderBy('import_runs.name')
            ->orderBy('import_runs.handle')
            ->whereNull('import_runs.dateDeleted');
    }

    // ///// import //////
    public function dispatchImport(ImportRun $run): bool
    {
        $steps = [];

        // for each step in the $run
        foreach ($run->steps as $key => $step) {
            $config = $this->getConfigByUid($step['config']) ?? $this->getConfigByHandle($step['config']);
            $file = $config->file ?? $step['file'];
            $filePath = BaseImporter::resolvedFilePath($file);

            // name for this batch of jobs
            $steps[$key]['name'] = $config->name;
            $steps[$key]['job'] = new ImportJob($step, $filePath, 0);

        }

        event($event = new ImportRunDispatching($steps, $run));

        if (! $event->isValid) {
            return false;
        }

        $steps = $event->steps;
        $run = $event->run;

        // TODO: think about scheduling batch pruning

        // we need to go through a single job because we want to name our chain
        dispatch(new ImportPipeline($steps, $run));

        event(new ImportRunDispatched($steps, $run));

        return true;
    }

    public function importItem(BaseImporter $config, array $data): void
    {
        event($event = new DataImporting($config, $data));

        if (! $event->isValid) {
            return;
        }

        $data = $event->data;

        // figure out if we're adding or updating
        $element = $this->getElement($config, $data);

        $item = $this->processData($config, $data, $element);

        $attributeHandles = $element->attributes();
        $fieldHandles = array_diff(array_keys($item), $attributeHandles);
        $attributes = array_filter(array_filter($item, fn ($value, $key) => in_array($key, $attributeHandles), ARRAY_FILTER_USE_BOTH));
        $fields = array_filter($item, fn ($value, $key) => in_array($key, $fieldHandles), ARRAY_FILTER_USE_BOTH);

        $element->setAttributesFromRequest($attributes);

        $fields = $this->normalizeNestedFields($element, $fields);

        $element->setFieldValues($fields);

        Elements::saveElement($element);

        event(new DataImported($config, $data));
    }

    private function normalizeNestedFields(ElementInterface $element, array $fields): array
    {
        $fieldLayout = $element->getFieldLayout();

        if (! $fieldLayout) {
            return $fields;
        }

        foreach ($fields as $handle => $value) {
            if (! is_array($value)) {
                continue;
            }

            $field = $fieldLayout->getFieldByHandle($handle);
            // if we don't have a field, or it's not an importable nested elements type field,
            // we don't have to worry about extra normalization, so carry on
            if (! $field instanceof ImportableElementContainerFieldInterface) {
                continue;
            }

            $fields[$handle] = $field->normalizeValueForImport($value, $element);
        }

        return $fields;
    }

    private function getElement(BaseImporter $config, array $data): ElementInterface
    {
        // figure out if we're adding or editing
        $element = new $config->className;

        // if null then return a brand new ElementInterface object with just the siteId set to the selected value
        if ($config->matchCriteria === null) {
            $element->siteId = $config->site->id;

            return $element;
        }

        if (is_array($config->matchCriteria)) {
            $query = $element::find();
            $criteria = [];

            foreach ($config->matchCriteria as $key => $value) {
                if (array_key_exists((string) $value, $data)) {
                    $criteria[$key] = $data[$value];
                }
            }

            if (empty($criteria)) {
                $element->siteId = $config->site?->id;

                return $element;
            }

            Typecast::configure($query, $criteria);
            // force the selected siteId
            $query->siteId = $config->site?->id;

            return $query->one() ?? $element;
        }

        return $element;
    }

    public function getData(string $filePath): array
    {
        error_clear_last();
        $rawData = @file_get_contents($filePath);
        $error = error_get_last();
        if ($error) {
            throw new Exception($error['message']);
        }

        if (! $rawData) {
            throw new Exception('Unable to parse data.');
        }

        // process raw data based on the file type it came from
        $data = $this->formatData($filePath, $rawData);

        if ($data === null || $data['success'] === false) {
            throw new Exception($data['error'] ?? 'Unable to parse data.');
        }

        return $data['data'];
    }

    private function formatData(string $filePath, string $rawData): ?array
    {
        $extension = File::extension($filePath);
        $dataTypes = $this->getAllDataTypes();

        if (! $dataTypes[$extension]) {
            throw new Exception('Unsupported data type: '.$extension);
        }

        try {
            $data = $dataTypes[$extension]::format($rawData);
        } catch (Throwable $e) {
            Log::error($e->getMessage());

            return null;
        }

        return $data;
    }

    private function processData(BaseImporter $config, array $data, mixed $element): array
    {
        // turn that data into a fractal collection
        $resource = new Item($data, $config->transformer);
        $resource->setMeta(['config' => $config, 'element' => $element]);

        // Load Fractal
        $fractalManager = new Manager;
        $fractalManager->setSerializer(new DataArraySerializer);

        //        // Parse includes/excludes
        //        $fractalManager->parseIncludes($includes);
        //        $fractalManager->parseExcludes($excludes);

        $data = $fractalManager->createData($resource);

        // todo: ->toArray() freaks out if the transformer is null; not sure if that's expected or not
        if ($config->transformer === null) {
            return $data->getResource()->getData();
        }

        $data = $data->toArray();

        return $data['data'];
    }
}
