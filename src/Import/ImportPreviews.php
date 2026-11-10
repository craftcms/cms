<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\ItemPreview;
use CraftCms\Cms\Import\Enums\ItemPreviewResult;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Throwable;

use function CraftCms\Cms\currentUser;
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
    /** How many of a step's items are listed at a time. */
    public const int PAGE_SIZE = 10;

    /**
     * How long a step's results are kept, in seconds. Paging and filtering them would otherwise preview every item
     * again, which takes a while for a step with thousands of them.
     */
    public const int CACHE_DURATION = 300;

    public function __construct(private Import $import) {}

    /**
     * Previews every one of the step's items, returning how many have each result, and a page of those with the one
     * asked for. A page past the last one is the last one.
     *
     * @return array{total: int, counts: array<string, int>, page: int, matchKeys: list<string>, items: list<array{index: int, result: string, title: string|null, match: array<string, string>, errors: list<string>}>}
     *
     * @throws Throwable when the step's data can't be read.
     */
    public function previewStep(ElementImporter $step, int $page = 0, ?ItemPreviewResult $result = null, int $pageSize = self::PAGE_SIZE): array
    {
        $data = $this->data($step);
        // the content can change in the meantime, so the results are only kept a short while, and for the step and
        // data they're of; and they're each user's own, as they name elements other users may not be able to see
        $key = 'import-preview:'.sha1(serialize([$step->toArrayData(), $data, currentUser()?->getAuthIdentifier(), Cache::get(self::versionKey($step))]));

        ['matchKeys' => $matchKeys, 'items' => $items] = Cache::remember($key, self::CACHE_DURATION, function () use ($step, $data): array {
            $matchCriteria = $this->prepare($step);
            $matchKeys = array_map(strval(...), array_keys($matchCriteria));
            $items = [];

            foreach ($data as $index => $item) {
                $items[] = $this->summarize($index, $this->preview($step, $item, $matchCriteria), $matchKeys);
            }

            return ['matchKeys' => $matchKeys, 'items' => $items];
        });

        $counts = array_count_values(array_column($items, 'result'));
        $matching = $result === null ? $items : array_values(array_filter($items, fn (array $item): bool => $item['result'] === $result->value));
        $page = max(0, min($page, (int) ceil(count($matching) / $pageSize) - 1));

        return [
            'total' => count($items),
            'counts' => array_map(fn (ItemPreviewResult $case): int => $counts[$case->value] ?? 0, array_column(ItemPreviewResult::cases(), null, 'value')),
            'page' => $page,
            'matchKeys' => $matchKeys,
            'items' => array_slice($matching, $page * $pageSize, $pageSize),
        ];
    }

    /**
     * Forgets steps' results, such as once their plan has been saved or run.
     *
     * @param  iterable<BaseImporter>  $steps
     */
    public function forgetSteps(iterable $steps): void
    {
        foreach ($steps as $step) {
            Cache::forever(self::versionKey($step), Str::random());
        }
    }

    /**
     * The key of a token that's part of the key a step's results are cached under, which forgets them all when it
     * changes, whoever they're for.
     */
    private static function versionKey(BaseImporter $step): string
    {
        return "import-preview-version:{$step->uid}";
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
     * @param  list<string>  $matchKeys
     * @return array{index: int, result: string, title: string|null, match: array<string, string>, errors: list<string>}
     */
    private function summarize(int $index, ItemPreview $preview, array $matchKeys): array
    {
        return [
            'index' => $index,
            'result' => $preview->result->value,
            'title' => $preview->element?->getUiLabel(),
            'match' => array_map(
                fn (mixed $value): string => is_scalar($value) ? (string) $value : (string) Json::encode($value),
                Arr::only($preview->matchCriteria, $matchKeys),
            ),
            'errors' => $preview->errors,
        ];
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
