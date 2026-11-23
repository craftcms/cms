<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\ItemPreview;
use CraftCms\Cms\Import\Enums\ItemPreviewResult;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * from the save, which takes long enough that thousands of them would keep the preview from ever being useful.
     */
    public const int MAX_ITEMS = 500;

    /**
     * How long a request for part of a preview keeps previewing items once it has a page of them, in seconds. The
     * requests that follow pick up where it left off.
     */
    public const float TIME_BUDGET = 1.0;

    public function __construct(private Import $import, private float $timeBudget = self::TIME_BUDGET) {}

    /**
     * Previews the step's first {@see MAX_ITEMS} items, returning how many have each result, and a page of those with
     * the one asked for. A page past the last one is the last one. `total` is how many were previewed, and `itemCount`
     * how many the step's data has.
     *
     * With `$partial`, it previews only some more of them, for about {@see TIME_BUDGET}, and answers from those
     * previewed so far; `pending` is how many are left, which the requests that follow preview.
     *
     * Items whose elements would change, which are the ones with something to compare, are counted through the pages
     * too: `previewable` is how many of those there are, and `previewableBefore` how many come before the page. Given
     * `$previewableIndex`, the page is the one with that one of them.
     *
     * @return array{total: int, itemCount: int, pending: int, counts: array<string, int>, page: int, previewable: int, previewableBefore: int, matchKeys: list<string>, items: list<array{index: int, result: string, title: string|null, match: array<string, string>, errors: list<string>}>}
     *
     * @throws Throwable when the step's data can't be read.
     */
    public function previewStep(ElementImporter $step, int $page = 0, ?ItemPreviewResult $result = null, int $pageSize = self::PAGE_SIZE, ?int $previewableIndex = null, bool $partial = false): array
    {
        // the results are only kept a short while, and for the step, the version of its local file, and the version of
        // the content they're of; and they're each user's own, as they name elements other users may not be able to see
        $key = 'import-preview:'.sha1(serialize([
            $step->toArrayData(),
            self::localSourceVersion($step),
            self::contentVersion(),
            currentUser()?->getAuthIdentifier(),
            Cache::get(self::versionKey($step)),
        ]));

        ['itemCount' => $itemCount, 'matchKeys' => $matchKeys, 'items' => $items, 'pending' => $pending] = $this->advance($step, $key, $partial ? $this->timeBudget : null);

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
            'pending' => $pending,
            'counts' => array_map(fn (ItemPreviewResult $case): int => $counts[$case->value] ?? 0, array_column(ItemPreviewResult::cases(), null, 'value')),
            'page' => $page,
            'previewable' => count($previewable),
            'previewableBefore' => count(array_filter($previewable, fn (int $position): bool => $position < $page * $pageSize)),
            'matchKeys' => $matchKeys,
            'items' => array_slice($matching, $page * $pageSize, $pageSize),
        ];
    }

    /**
     * Previews more of the step's items, from where the last request for them left off, and returns those previewed so
     * far. Without a budget it previews all of them; with one, a page of them and then as many more as it has time for.
     * The items still to preview are kept with the results, so a remote source is only read once.
     *
     * @return array{itemCount: int, matchKeys: list<string>, items: list<array{index: int, result: string, title: string|null, match: array<string, string>, errors: list<string>}>, pending: int}
     *
     * @throws Throwable when the step's data can't be read.
     */
    private function advance(ElementImporter $step, string $key, ?float $budget): array
    {
        // one request at a time, so two don't preview the same items
        return Cache::lock("{$key}:lock", 120)->block(120, function () use ($step, $key, $budget): array {
            /** @var array{itemCount: int, matchKeys: list<string>|null, items: list<array{index: int, result: string, title: string|null, match: array<string, string>, errors: list<string>}>, data?: array<int, mixed>}|null $state */
            $state = Cache::get($key);

            if (! is_array($state)) {
                $data = $this->data($step);
                $state = ['itemCount' => count($data), 'matchKeys' => null, 'items' => [], 'data' => array_slice($data, 0, self::MAX_ITEMS)];
            }

            $done = count($state['items']);
            $remaining = isset($state['data']) ? array_slice($state['data'], $done, preserve_keys: true) : [];

            if ($remaining !== [] || $state['matchKeys'] === null) {
                $matchCriteria = $this->prepare($step);
                $state['matchKeys'] ??= array_map(strval(...), array_keys($matchCriteria));
                $started = microtime(true);

                foreach ($remaining as $index => $item) {
                    $state['items'][] = $this->summarize($index, $this->preview($step, $item, $matchCriteria), $state['matchKeys']);

                    if ($budget !== null && count($state['items']) - $done >= self::PAGE_SIZE && microtime(true) - $started >= $budget) {
                        break;
                    }
                }

                if (count($state['items']) === count($state['data'] ?? [])) {
                    unset($state['data']);
                }

                Cache::put($key, $state, self::CACHE_DURATION);
            }

            return [
                'itemCount' => $state['itemCount'],
                'matchKeys' => $state['matchKeys'],
                'items' => $state['items'],
                'pending' => isset($state['data']) ? count($state['data']) - count($state['items']) : 0,
            ];
        });
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
     * When and how any element was last saved, deleted, or restored, so results are previewed again once the content
     * changes. Every element counts, as an item's result can depend on elements it relates to, not only on the one
     * it goes into.
     *
     * @return array<string, mixed>
     */
    private static function contentVersion(): array
    {
        $grammar = DB::getQueryGrammar();

        return (array) DB::table(Table::ELEMENTS)
            ->selectRaw(sprintf(
                'COUNT(*) AS %s, MAX(%s) AS %s, MAX(%s) AS %s',
                $grammar->wrap('count'),
                $grammar->wrap('dateUpdated'),
                $grammar->wrap('dateUpdated'),
                $grammar->wrap('dateDeleted'),
                $grammar->wrap('dateDeleted'),
            ))
            ->first();
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
