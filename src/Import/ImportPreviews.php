<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
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
     * again, and read a remote source again, which takes a while for a step with hundreds of items.
     */
    public const int CACHE_DURATION = 300;

    /**
     * How many of a step's items are previewed, from the first. Each is put through everything an import does apart
     * from the save, in the request that asks for the preview, so a step with thousands of them would outlast it.
     */
    public const int MAX_ITEMS = 500;

    public function __construct(private Import $import) {}

    /**
     * Previews the step's first {@see MAX_ITEMS} items, returning how many have each result, and a page of those with
     * the one asked for. A page past the last one is the last one. `total` is how many were previewed, and `itemCount`
     * how many the step's data has.
     *
     * Items whose elements would change, which are the ones with something to compare, are counted through the pages
     * too: `previewable` is how many of those there are, and `previewableBefore` how many come before the page. Given
     * `$previewableIndex`, the page is the one with that one of them.
     *
     * @return array{total: int, itemCount: int, counts: array<string, int>, page: int, previewable: int, previewableBefore: int, matchKeys: list<string>, items: list<array{index: int, result: string, title: string|null, match: array<string, string>, errors: list<string>}>}
     *
     * @throws Throwable when the step's data can't be read.
     */
    public function previewStep(ElementImporter $step, int $page = 0, ?ItemPreviewResult $result = null, int $pageSize = self::PAGE_SIZE, ?int $previewableIndex = null): array
    {
        // the content can change in the meantime, so the results are only kept a short while, and for the step and
        // the version of its local file they're of; and they're each user's own, as they name elements other users may
        // not be able to see
        $key = 'import-preview:'.sha1(serialize([
            $step->toArrayData(),
            self::localSourceVersion($step),
            currentUser()?->getAuthIdentifier(),
            Cache::get(self::versionKey($step)),
        ]));

        ['itemCount' => $itemCount, 'matchKeys' => $matchKeys, 'items' => $items] = Cache::remember($key, self::CACHE_DURATION, function () use ($step): array {
            $data = $this->data($step);
            $matchCriteria = $this->prepare($step);
            $matchKeys = array_map(strval(...), array_keys($matchCriteria));
            $items = [];

            foreach (array_slice($data, 0, self::MAX_ITEMS) as $index => $item) {
                $items[] = $this->summarize($index, $this->preview($step, $item, $matchCriteria), $matchKeys);
            }

            return ['itemCount' => count($data), 'matchKeys' => $matchKeys, 'items' => $items];
        });

        $counts = array_count_values(array_column($items, 'result'));
        $matching = $result === null ? $items : array_values(array_filter($items, fn (array $item): bool => $item['result'] === $result->value));
        // the positions of the items with something to compare, among those paged through
        $previewable = array_keys(array_filter($matching, fn (array $item): bool => ! in_array($item['result'], [ItemPreviewResult::Skip->value, ItemPreviewResult::Unchanged->value], true)));

        if ($previewableIndex !== null && isset($previewable[$previewableIndex])) {
            $page = intdiv($previewable[$previewableIndex], $pageSize);
        }

        $page = max(0, min($page, (int) ceil(count($matching) / $pageSize) - 1));

        return [
            'total' => count($items),
            'itemCount' => $itemCount,
            'counts' => array_map(fn (ItemPreviewResult $case): int => $counts[$case->value] ?? 0, array_column(ItemPreviewResult::cases(), null, 'value')),
            'page' => $page,
            'previewable' => count($previewable),
            'previewableBefore' => count(array_filter($previewable, fn (int $position): bool => $position < $page * $pageSize)),
            'matchKeys' => $matchKeys,
            'items' => array_slice($matching, $page * $pageSize, $pageSize),
        ];
    }

    /**
     * When a step's source is a local file, when and how it was last written, so results are previewed again once
     * it changes. A remote source isn't fetched again until the results expire.
     */
    private static function localSourceVersion(ElementImporter $step): ?string
    {
        try {
            $path = BaseImporter::resolvedSourcePath($step->source);
        } catch (Throwable) {
            return null;
        }

        if ($path === null || BaseImporter::isRemoteSource($step->source) || ! is_file($path)) {
            return null;
        }

        return filemtime($path).':'.filesize($path);
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
     * What an item's element is called, or `null` when it has no title yet, which the preview labels as untitled
     * however it's shown, rather than with each element type's own placeholder.
     */
    public static function itemTitle(?ElementInterface $element): ?string
    {
        if ($element === null) {
            return null;
        }

        return $element::hasTitles() ? ($element->title ?: null) : $element->getUiLabel();
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
            'title' => self::itemTitle($preview->element),
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
