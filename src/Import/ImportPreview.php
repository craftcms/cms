<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Stringable;
use Throwable;

use function CraftCms\Cms\t;

/**
 * What running a step would do, worked out from its data without importing anything: the
 * values each row fills its destinations with, and whether it creates or updates an element.
 *
 * @since 6.0.0
 */
readonly class ImportPreview
{
    public function __construct(
        private Import $importService,
    ) {}

    /**
     * A step's first rows, as the values they'd fill each mapped destination with, and for an
     * element step, whether each would create an element or update one its matching finds.
     *
     * @return array{columns: list<array{key: string, label: string}>, rows: list<array{row: int, action: 'create'|'update'|'skip'|null, label: string|null, url: string|null, values: array<string, string>}>, created: int|null, updated: int|null, skipped: int|null, total: int, error: string|null}
     */
    public function review(BaseImporter $importer, int $limit = 50): array
    {
        $isElementStep = $importer instanceof ElementImporter;

        try {
            $items = $this->items($importer);
            $cols = $this->leafCols($importer->getDestinationCols());
            // with nothing to match on, every row is new
            $found = $isElementStep && ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer) !== []
                ? $this->importService->findMatches($importer, $items, PHP_INT_MAX)['matches']
                : [];
        } catch (Throwable $e) {
            return ['columns' => [], 'rows' => [], 'created' => null, 'updated' => null, 'skipped' => null, 'total' => 0, 'error' => $e->getMessage()];
        }

        $matches = array_column($found, 'element', 'row');
        $counts = ['create' => 0, 'update' => 0, 'skip' => 0];

        foreach (array_keys($items) as $index) {
            if ($isElementStep) {
                $counts[$this->actionFor($importer, isset($matches[$index + 1]))]++;
            }
        }

        $rows = [];
        $filled = [];

        foreach (array_slice($items, 0, $limit) as $index => $item) {
            $row = $index + 1;
            $data = $this->remap($importer, $item);
            $element = $matches[$row] ?? null;
            $values = [];

            foreach ($cols as $key => $col) {
                $display = self::display(data_get($data, $col['col']->prefixedHandleAsArray));

                if ($display !== null) {
                    $values[$key] = $display;
                    $filled[$key] = true;
                }
            }

            $rows[] = [
                'row' => $row,
                'action' => $isElementStep ? $this->actionFor($importer, $element !== null) : null,
                'label' => $element?->getUiLabel(),
                'url' => $element?->getCpEditUrl(),
                'values' => $values,
            ];
        }

        $columns = [];

        foreach ($cols as $key => $col) {
            if (isset($filled[$key])) {
                $columns[] = ['key' => $key, 'label' => $col['label']];
            }
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'created' => $isElementStep ? $counts['create'] : null,
            'updated' => $isElementStep ? $counts['update'] : null,
            'skipped' => $isElementStep ? $counts['skip'] : null,
            'total' => count($items),
            'error' => null,
        ];
    }

    /** What a row does, given whether its match criteria found an existing element. */
    private function actionFor(BaseImporter $importer, bool $matched): string
    {
        if ($importer instanceof ElementImporter && ! $importer->matchMode->imports($matched)) {
            return 'skip';
        }

        return $matched ? 'update' : 'create';
    }

    /**
     * A step's data source as it reads: its first items, keyed by the paths its columns use, so
     * the source can be checked before it's mapped.
     *
     * @return array{columns: list<string>, rows: list<array<string, string>>, total: int, error: string|null}
     */
    public function source(BaseImporter $importer, int $limit = 5): array
    {
        try {
            $items = $this->items($importer);
        } catch (Throwable $e) {
            return ['columns' => [], 'rows' => [], 'total' => 0, 'error' => $e->getMessage()];
        }

        // items needn't share their keys, so a few of them are looked at for the columns
        $columns = [];

        foreach (array_slice($items, 0, 20) as $item) {
            foreach (is_array($item) ? Arr::uniqueDotifiedKeys($item) : [] as $key) {
                $columns[$key] = true;
            }
        }

        $columns = array_keys($columns);
        $rows = [];

        foreach (array_slice($items, 0, $limit) as $item) {
            $row = [];

            foreach ($columns as $key) {
                $display = is_array($item) ? self::display(Arr::sampleValueAtDotifiedKey($item, $key)) : null;

                if ($display !== null) {
                    $row[$key] = $display;
                }
            }

            $rows[] = $row;
        }

        return ['columns' => $columns, 'rows' => $rows, 'total' => count($items), 'error' => null];
    }

    /** @return list<mixed> */
    private function items(BaseImporter $importer): array
    {
        if (($error = BaseImporter::sourceError($importer->source, resolveHost: false)) !== null) {
            throw new RuntimeException($error);
        }

        $importer->prepareForImport();

        return $importer->withLocalFile(fn (string $filePath) => array_values($this->importService->getFormattedData($filePath)));
    }

    /** @return array<mixed> */
    private function remap(BaseImporter $importer, mixed $item): array
    {
        return is_array($item) ? ImportHelper::remapData($importer->map, $item, $importer->templates ?? []) : [];
    }

    /**
     * The destinations a row's values are shown under, with a compound column's own heading
     * kept on each of its subfields.
     *
     * @param  list<MappingColumn|CompoundMappingColumn>  $cols
     * @return array<string, array{col: MappingColumn, label: string}>
     */
    private function leafCols(array $cols, string $heading = ''): array
    {
        $leaves = [];

        foreach ($cols as $col) {
            if ($col instanceof CompoundMappingColumn) {
                $leaves += $this->leafCols($col->subfields, $col->heading ? "{$col->heading} › " : '');

                continue;
            }

            // a match-only value is never written, so it has nothing to review
            if ($col->canBeSet === false) {
                continue;
            }

            $leaves[$col->prefixedHandle] = ['col' => $col, 'label' => $heading.$col->label];
        }

        return $leaves;
    }

    /** A value as it reads in a preview cell, or null when there's nothing to show. */
    private static function display(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return Str::limit((string) $value, 120);
        }

        if (is_array($value) && array_is_list($value) && array_filter($value, is_scalar(...)) === $value) {
            return Str::limit(implode(', ', $value), 120);
        }

        $count = is_countable($value) ? count($value) : 1;

        return t('{count, plural, =1{# item} other{# items}}', ['count' => $count]);
    }
}
