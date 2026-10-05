<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Data\MappingValues;
use CraftCms\Cms\Import\Data\SourceColumn;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\ImportHelper;

/**
 * @since 6.0.0
 */
class ImportPlanMapViewModel extends ViewModel
{
    /** @var list<MappingColumn|CompoundMappingColumn>|null */
    private ?array $destinationCols = null;

    /** @var list<SourceColumn>|null */
    private ?array $sourceDataCols = null;

    public function __construct(
        private readonly BaseImporter $importer,
        private readonly bool $canSave = true,
    ) {}

    /** @return array{uid: string|null, source: string|null} */
    public function step(): array
    {
        return [
            'uid' => $this->importer->uid,
            'source' => $this->importer->source,
        ];
    }

    /** @return list<MappingColumn|CompoundMappingColumn> */
    public function destinationCols(): array
    {
        return $this->destinationCols ??= $this->importer->getDestinationCols();
    }

    /** @return list<SourceColumn> */
    public function sourceDataCols(): array
    {
        // memoized alongside destinationCols(): both are asked for twice per page — once for
        // the screen, once to work out the suggestions — and this one parses the data file
        return $this->sourceDataCols ??= $this->importer->getSourceDataCols() ?? [];
    }

    /**
     * The mapping state the page edits, as nested objects keyed the same way as each
     * column's `prefixedHandleAsArray`.
     */
    public function values(): MappingValues
    {
        return new MappingValues(
            map: $this->importer->map,
            matchCriteria: $this->importer->matchCriteria ?? [],
            clearableItems: $this->importer->clearableItems ?? [],
            keepMissingNestedElements: $this->importer instanceof ElementImporter
                ? $this->importer->keepMissingNestedElements ?? []
                : [],
            fieldSettings: $this->importer instanceof ElementImporter
                ? $this->importer->fieldSettings ?? []
                : [],
        );
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

    public function canSave(): bool
    {
        return $this->canSave;
    }
}
