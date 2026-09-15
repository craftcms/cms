<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Http\Controllers\Import\ImportConfigController;
use CraftCms\Cms\Import\Importers\BaseImporter;

class ImportMapViewModel extends ViewModel
{
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
        return $this->importer->getDestinationCols();
    }

    /** @return array<array-key, mixed> */
    public function sourceDataCols(): array
    {
        return $this->importer->getSourceDataCols();
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
            'keepMissingNestedElements' => $this->importer->keepMissingNestedElements ?? [],
        ];
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
