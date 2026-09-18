<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Http\Controllers\Import\ImportConfigController;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\ImportHelper;

class ImportMapViewModel extends ViewModel
{
    private ?array $destinationCols = null;

    private ?array $sourceDataCols = null;

    public function __construct(
        private readonly BaseImporter $importer,
        private readonly bool $readOnly = false,
        private readonly bool $canSave = true,
    ) {}

    /** @return array{uid: string|null, handle: string|null, name: string|null, file: string|null} */
    public function config(): array
    {
        return [
            'uid' => $this->importer->uid,
            'handle' => $this->importer->handle,
            'name' => $this->importer->name,
            'file' => $this->importer->file,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function destinationCols(): array
    {
        return $this->destinationCols ??= $this->importer->getDestinationCols();
    }

    /** @return array<array-key, mixed> */
    public function sourceDataCols(): array
    {
        // memoized alongside destinationCols(): both are asked for twice per page — once for
        // the screen, once to work out the suggestions — and this one parses the data file
        return $this->sourceDataCols ??= $this->importer->getSourceDataCols();
    }

    /**
     * The mapping state the page edits, as nested objects keyed the same way as each
     * column's `prefixedHandleAsArray`.
     *
     * @return array<string, array<array-key, mixed>>
     */
    public function values(): array
    {
        return [
            'map' => $this->importer->map,
            'matchCriteria' => $this->importer->matchCriteria ?? [],
            'clearableItems' => $this->importer->clearableItems ?? [],
            'keepMissingNestedElements' => $this->importer instanceof ElementImporter
                ? $this->importer->keepMissingNestedElements ?? []
                : [],
        ];
    }

    /**
     * A best guess at a source column for each leaf the map doesn't already have a value at,
     * keyed the same way as the map. The screen fills them in and flags them as guesses.
     *
     * @return array<array-key, mixed>
     */
    public function suggestions(): array
    {
        return ImportHelper::suggestMapValues(
            $this->destinationCols(),
            $this->sourceDataCols(),
            $this->importer->map,
        );
    }

    /** @return array{method: 'post', url: string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([ImportConfigController::class, 'storeMap']),
        ];
    }

    public function nestedColsUrl(): string
    {
        return action([ImportConfigController::class, 'nestedMappingCols']);
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
