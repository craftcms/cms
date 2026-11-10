<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\ItemPreview;
use CraftCms\Cms\Import\Enums\ItemPreviewResult;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Runs an import step over its data without saving anything, to show what running it would do.
 *
 * Each step is previewed on its own, against the content as it is now, so a step that relates to elements an earlier
 * step would create can't find them.
 *
 * @since 6.0.0
 */
readonly class ImportPreviews
{
    /** How many of a step's items are previewed. */
    public const int LIMIT = 50;

    public function __construct(private Import $import) {}

    /**
     * Previews the step's first items.
     *
     * @return array{total: int, matchKeys: list<string>, items: array<int, ItemPreview>} The items are keyed by their index in
     *                                                                                    the step's data; `matchKeys` are the handles they're matched on.
     *
     * @throws Throwable when the step's data can't be read.
     */
    public function previewStep(ElementImporter $step, int $limit = self::LIMIT): array
    {
        $data = $this->data($step);
        $matchCriteria = $this->prepare($step);
        $items = [];

        foreach (array_slice($data, 0, $limit, true) as $index => $item) {
            $items[$index] = $this->preview($step, $item, $matchCriteria);
        }

        return ['total' => count($data), 'matchKeys' => array_map(strval(...), array_keys($matchCriteria)), 'items' => $items];
    }

    /**
     * Previews one of the step's items, or returns `null` when there's no item at that index.
     *
     * @throws Throwable when the step's data can't be read.
     */
    public function previewItem(ElementImporter $step, int $index): ?ItemPreview
    {
        $data = $this->data($step);

        if (! isset($data[$index])) {
            return null;
        }

        return $this->preview($step, $data[$index], $this->prepare($step));
    }

    /**
     * @return list<mixed>
     */
    private function data(ElementImporter $step): array
    {
        // checks the source can be read from, as an import run does
        $step->validate();

        return array_values($step->withLocalFile(fn (string $filePath) => $this->import->getFormattedData($filePath, $step)));
    }

    /**
     * Readies the step as an import run does, returning the match criteria its items go through.
     *
     * @return array<string, mixed>
     */
    private function prepare(ElementImporter $step): array
    {
        $step->prepareForImport();

        return ImportHelper::normalizeMatchCriteriaFromImporterConfig($step);
    }

    /**
     * @param  array<string, mixed>  $matchCriteria
     */
    private function preview(ElementImporter $step, mixed $item, array $matchCriteria): ItemPreview
    {
        if (! is_array($item)) {
            return new ItemPreview(ItemPreviewResult::Problem, null, errors: [t('This item isn’t a set of values.')]);
        }

        try {
            return $this->import->previewItem($step, $item, $matchCriteria);
        } catch (Throwable $e) {
            return new ItemPreview(ItemPreviewResult::Problem, null, errors: [$e->getMessage()]);
        }
    }
}
