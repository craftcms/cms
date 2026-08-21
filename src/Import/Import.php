<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Import\DataTypes\Csv;
use CraftCms\Cms\Import\DataTypes\Json;
use CraftCms\Cms\Import\DataTypes\Xml;
use CraftCms\Cms\Import\Events\DataImported;
use CraftCms\Cms\Import\Events\DataImporting;
use CraftCms\Cms\Import\Events\ImportRunDispatched;
use CraftCms\Cms\Import\Events\ImportRunDispatching;
use CraftCms\Cms\Import\Events\RegisterDataTypes;
use CraftCms\Cms\Import\Events\RegisterImporterTypes;
use CraftCms\Cms\Import\Exceptions\InvalidConfigException;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Importers\ModelImporter;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ImportConfig;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\ImportHelper;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;
use Throwable;

#[Singleton]
class Import
{
    /**
     * Returns the available data type classes, keyed by extension.
     * The list includes built-in json/csv/xml data type map, extended via `RegisterDataTypes` event listeners.
     */
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

    /**
     * Returns the available importer classes.
     * The list includes built-in Element/Model importer classes, extended via `RegisterImporterTypes` event.
     *
     * @return array The available importer classes.
     */
    public function getAllImporterTypes(): array
    {
        $importers = [
            ElementImporter::class,
            ModelImporter::class,
        ];

        if (Event::hasListeners(RegisterImporterTypes::class)) {
            Event::dispatch($event = new RegisterImporterTypes($importers));

            $importers = $event->importers;
        }

        return $importers;
    }

    /**
     * Resolves each step's config/file into a queued Import job, fires dispatching/dispatched events, and dispatches an ImportPipeline job chain.
     *
     * @param  ImportRun  $run  The import run to dispatch.
     */
    public function dispatchImport(ImportRun $run): bool
    {
        $steps = [];

        // for each step in the $run
        foreach ($run->steps as $key => $step) {
            $config = ImportConfig::getConfigByUid($step['config']) ?? ImportConfig::getConfigByHandle($step['config']);
            if (! $config) {
                throw new InvalidConfigException($step['config']);
            }

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

        // todo (iwona): think about scheduling batch pruning

        // we need to go through a single job because we want to name our chain
        dispatch(new ImportPipeline($steps, $run));

        event(new ImportRunDispatched($steps, $run));

        return true;
    }

    /**
     * Fires the importing event, applies map remapping, resolves/applies match criteria and clearable items, then hands the item to the importer and fires the imported event.
     *
     * @param  BaseImporter  $importer  The importer config to import into.
     * @param  array  $data  The raw item data being imported.
     */
    public function importItem(BaseImporter $importer, array $data): void
    {
        event($event = new DataImporting($importer, $data));

        if (! $event->isValid) {
            return;
        }

        $data = $event->data;

        // if we have a map here, hook it up; if we have both the map and the transformer,
        // then the map is used first and then transformer can do further manipulation;
        // if there's no map, the transformer acts as one on its own;
        if ($importer->map) {
            $data = ImportHelper::remapData($importer->map, $data);
        }

        // todo: if we decide to include the transformer matchCriteria later on
        // (e.g. because we want it to be able to match directly to a value and not necessarily just the data key),
        // then we might want to do this once per config and not for each root item that is being imported
        $matchCriteria = $this->normalizeMatchCriteria($importer);

        // this should continue to be executed on per-item basis
        $this->resolveMatchCriteria($data, $matchCriteria);

        $this->applyClearableItems($data, $importer->clearableItems ?? []);

        $importer->importItem($data);

        event(new DataImported($importer, $data));
    }

    /**
     * Normalizes match criteria into an array where keys are the fields/attributes/properties to update,
     * and values containing the incoming data keys.
     */
    private function normalizeMatchCriteria(BaseImporter $importer): array
    {
        // the order of importance is:
        // matchCriteria coming from the incoming data are overwritten by
        // matchCriteria coming from the UI or file-based config (those two never exist together), are overwritten by

        $map = $importer->map;

        // the matchCriteria that are coming from the UI or from a file-based config
        $matchCriteria = $importer->matchCriteria;

        // ones coming from the UI will have a value of 1
        // ones coming from the file-based config should be strings that point to the original data keys

        // for the ones coming from the UI - we need to resolve those to the mapped column names
        $dottedMap = Arr::dot($map);
        $dottedMatchCriteria = Arr::dot($matchCriteria);

        foreach ($dottedMatchCriteria as $key => $value) {
            if (is_numeric($value) && $value == 1) {
                $dottedMatchCriteria[$key] = $dottedMap[$key];
            }
        }

        return Arr::undot($dottedMatchCriteria);
    }

    /**
     * Resolves the match criteria so that the returned array contains keys that represent the fields/attributes/properties to update
     * and values containing the incoming data values (not keys).
     */
    private function resolveMatchCriteria(array &$data, array $criteria): void
    {
        $levelCriteria = [];
        foreach ($criteria as $key => $value) {
            if ($value !== null && ! is_array($value) && isset($data[$key])/* array_key_exists($key, $data) */) {
                $levelCriteria[$key] = $data[$key];
            }
        }

        $existingMatchCriteria = $data['matchCriteria'] ?? [];

        if (! empty($existingMatchCriteria)) {
            $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
            $existingMatchCriteria = array_map(
                function ($value) use ($data, $fields) {
                    if (! is_string($value)) {
                        return $value;
                    }

                    return $data[$value] ?? $fields[$value] ?? $value;
                },
                $existingMatchCriteria
            );
        }

        if (! empty($levelCriteria) || ! empty($existingMatchCriteria)) {
            $matchCriteria = array_merge($existingMatchCriteria, $levelCriteria);
            $data = ['matchCriteria' => $matchCriteria] + $data;
        }

        // walk every array-valued key present in either the data or the configured criteria, not
        // just ones the importer's own criteria config happens to mention, so that inline
        // matchCriteria on blocks the config doesn't cover (e.g. a container field the importer
        // never configured matching for) still gets resolved.
        $keys = array_unique(array_merge(array_keys($criteria), array_keys($data)));

        foreach ($keys as $key) {
            if ($key === 'matchCriteria') {
                continue;
            }
            if (! isset($data[$key])) {
                continue;
            }
            if (! is_array($data[$key])) {
                continue;
            }

            $value = $criteria[$key] ?? [];

            if (! is_array($value)) {
                continue;
            }

            if (array_is_list($data[$key])) {
                foreach ($data[$key] as &$item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    if (! isset($item['type'])) {
                        continue;
                    }
                    $this->resolveMatchCriteria($item, $value[$item['type']] ?? []);
                }
                unset($item);
            } else {
                $this->resolveMatchCriteria($data[$key], $value);
            }
        }
    }

    /**
     * Clears out any handles marked as clearable in $clearableItems whose incoming value is missing or empty,
     * forcing them to null so they're explicitly applied (rather than silently left untouched) further downstream.
     */
    private function applyClearableItems(array &$data, array $clearableItems): void
    {
        foreach ($clearableItems as $key => $value) {
            if (! is_array($value)) {
                if ($value && (! array_key_exists($key, $data) || self::isEmptyValue($data[$key]))) {
                    $data[$key] = null;
                }

                continue;
            }
            if (! isset($data[$key])) {
                continue;
            }
            if (! is_array($data[$key])) {
                continue;
            }

            if (! empty($data[$key]) && array_is_list($data[$key]) && ! array_is_list($value)) {
                foreach ($data[$key] as &$item) {
                    if (is_array($item) && isset($item['type'], $value[$item['type']])) {
                        $this->applyClearableItems($item, $value[$item['type']]);
                    }
                }
                unset($item);
            } else {
                $this->applyClearableItems($data[$key], $value);
            }
        }

        // anything left over that's empty/null and isn't explicitly marked clearable should never
        // reach the importer at all, so the existing value it maps to is left completely untouched
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $clearableItems) && $clearableItems[$key] !== null) {
                continue;
            }

            if (self::isEmptyValue($value)) {
                unset($data[$key]);
            }
        }
    }

    /**
     * Determines whether a value should be treated as "empty" for the purposes of clearableItems.
     */
    private static function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    // todo (iwona): might be able to delete this; currently only used by ImportConfigController::run()
    /**
     * Reads and formats the importer's source file, then imports each item one by one.
     *
     * @param  BaseImporter  $importer  The importer config to use.
     */
    public function import(BaseImporter $importer): void
    {
        $filePath = BaseImporter::resolvedFilePath($importer->file);
        foreach ($this->getFormattedData($filePath) as $item) {
            $this->importItem($importer, $item);
        }
    }

    // //////////// data //////////////
    /**
     * Returns a file's raw contents, throwing if the read fails or the file is empty.
     *
     * @param  string  $filePath  The path to the file to read.
     */
    public function getRawData(string $filePath): string
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

        return $rawData;
    }

    /**
     * Reads raw file data and formats it via the appropriate data type.
     * Return the formatted data, throws on failure.
     *
     * @param  string  $filePath  The path to the file to read and format.
     */
    public function getFormattedData(string $filePath): array
    {
        $rawData = $this->getRawData($filePath);

        // process raw data based on the file type it came from
        $data = $this->formatData($filePath, $rawData);

        if ($data === null || $data['success'] === false) {
            throw new Exception($data['error'] ?? 'Unable to parse data.');
        }

        return $data['data'];
    }

    /**
     * Determines the data type from the file extension and delegates formatting to that type's `format()`, logging and returning null on error.
     */
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
            ImportLog::error($e->getMessage());

            return null;
        }

        return $data;
    }

    /**
     * Reads raw file data and returns the source column headings (prefixed with a "Please select" placeholder), logging and returning null on error.
     *
     * @param  string  $filePath  The path to the file to read.
     */
    public function getDataHeadings(string $filePath): ?array
    {
        $rawData = $this->getRawData($filePath);
        $extension = File::extension($filePath);
        $dataTypes = $this->getAllDataTypes();

        if (! $dataTypes[$extension]) {
            throw new Exception('Unsupported data type: '.$extension);
        }

        try {
            $headings = $dataTypes[$extension]::getHeadings($rawData);
        } catch (Throwable $e) {
            ImportLog::error($e->getMessage());

            return null;
        }

        return array_merge([['label' => 'Please select', 'value' => '']], $headings);
    }

    /**
     * Runs a Fractal transformer over the raw item data (with config/element as meta) and returns the transformed array.
     *
     * @param  BaseImporter  $importer  The importer config providing the transformer.
     * @param  array  $data  The raw item data to transform.
     * @param  mixed  $element  The element associated with the item, if any.
     */
    final public function processData(BaseImporter $importer, array $data, mixed $element): array
    {
        // turn the data from the json/csv/xml file into a fractal collection
        $resource = new Item($data, $importer->transformer);
        $resource->setMeta(['config' => $importer, 'element' => $element]);

        // Load Fractal
        $fractalManager = new Manager;
        $fractalManager->setSerializer(new DataArraySerializer);

        //        // Parse includes/excludes
        //        $fractalManager->parseIncludes($includes);
        //        $fractalManager->parseExcludes($excludes);

        $fractalData = $fractalManager->createData($resource);

        // note: ->toArray() freaks out if the transformer is null; not sure if that's expected or not
        if ($fractalData->getResource()->getTransformer() === null) {
            return $fractalData->getResource()->getData();
        }

        $data = $fractalData->toArray();

        return $data['data'];
    }
}
