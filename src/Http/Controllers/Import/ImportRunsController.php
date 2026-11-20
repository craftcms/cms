<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Import;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Data\StepSummary;
use CraftCms\Cms\Import\Enums\ImportItemOutcome;
use CraftCms\Cms\Import\Enums\ItemPreviewResult;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportPreviews;
use CraftCms\Cms\Import\ImportRuns;
use CraftCms\Cms\Import\Models\ImportRun;
use CraftCms\Cms\Import\Models\ImportRunItem;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * The record of an import plan's runs: each of them, and what one of them did.
 *
 * @since 6.0.0
 */
class ImportRunsController
{
    /**
     * The result the preview gives an item that comes to each outcome in a run.
     */
    private const array OUTCOME_RESULTS = [
        'created' => ItemPreviewResult::New,
        'updated' => ItemPreviewResult::Update,
        'unchanged' => ItemPreviewResult::Unchanged,
        'skipped' => ItemPreviewResult::Skip,
        'failed' => ItemPreviewResult::Problem,
    ];

    public function __construct(
        private readonly ImportPlans $importPlans,
        private readonly ImportRuns $importRuns,
        private readonly Import $import,
        private readonly ImportPreviews $importPreviews,
    ) {}

    /**
     * Every plan's runs, newest first, or just one plan's.
     */
    public function index(Request $request): InertiaResponse
    {
        $plans = $this->importPlans->getAllImportPlans();
        $filter = $request->string('plan')->toString() ?: null;
        $filterPlan = $filter !== null ? $plans->first(fn (ImportPlanData $plan) => $plan->handle === $filter) : null;
        $query = $filterPlan !== null ? $this->importRuns->forPlan($filterPlan) : ImportRun::query();

        // a run knows its plan by UID, or by handle for a plan registered in code without one
        $planKey = fn (?string $uid, ?string $handle): string => $uid ?? "handle:$handle";
        $plansByKey = $plans->keyBy(fn (ImportPlanData $plan) => $planKey($plan->uid, $plan->handle));

        return Inertia::render('import/Runs', [
            'title' => t('Activity'),
            'crumbs' => [new ActionItem()->label(t('Import'))->href(route('craft.cp.import.index'))],
            'plans' => $plans
                ->map(fn (ImportPlanData $plan): array => ['label' => (string) $plan->name, 'value' => (string) $plan->handle])
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
            'plan' => $filterPlan?->handle,
            'runs' => $query
                ->with('user')
                ->latest('dateCreated')
                ->latest('id')
                ->get()
                ->map(fn (ImportRun $run): array => $this->runRow($run, $plansByKey->get($planKey($run->planUid, $run->planHandle))))
                ->all(),
        ]);
    }

    public function show(Request $request, string $runId): InertiaResponse
    {
        [$run, $importPlan] = $this->findRun($runId);

        $interrupted = ! $run->status->isFinished()
            && ! $this->import->isRunning($importPlan)
            && $run->is($this->importRuns->latest($importPlan));

        return Inertia::render('import/Run', [
            'title' => t('Run of {name}', ['name' => $importPlan->name]),
            'crumbs' => [
                new ActionItem()->label(t('Import'))->href(route('craft.cp.import.index')),
                new ActionItem()->label(t('Activity'))->href(route('craft.cp.import.activity.index', ['plan' => $importPlan->handle])),
            ],
            'run' => $this->importRuns->toArray($run, $interrupted),
            'previewSteps' => $this->previewSteps($importPlan),
            'stepSummaries' => (object) StepSummary::forPlan($importPlan),
            'skippedInPreview' => Inertia::defer(fn (): object => (object) $this->skippedInPreview($run, $importPlan)),
            // the first page of each step's items, of every result
            'items' => fn (): object => (object) collect($run->steps)
                ->filter(fn (array $step): bool => array_sum($step['counts']) > 0)
                ->map(fn (array $step, string $stepUid): array => $this->stepItems($run, $importPlan, $stepUid, null, $request->craftUser()))
                ->all(),
        ]);
    }

    /**
     * A page of a step run's items, of one result or all of them, as its table lists them.
     */
    public function items(Request $request, string $runId): JsonResponse
    {
        [$run, $importPlan] = $this->findRun($runId);
        $stepUid = (string) $request->input('step');
        $result = ItemPreviewResult::tryFrom((string) $request->input('result'));
        $outcome = $result !== null ? ImportItemOutcome::tryFrom((string) array_search($result, self::OUTCOME_RESULTS, true)) : null;

        abort_unless(isset($run->steps[$stepUid]), 404, 'Import run step not found.');

        return new JsonResponse($this->stepItems(
            $run,
            $importPlan,
            $stepUid,
            $outcome,
            $request->craftUser(),
            $request->integer('page'),
        ));
    }

    /**
     * One of a run's failed items, rendered as a JSON file's preview is, from the data the run kept for it.
     */
    public function itemData(Request $request, string $runId): JsonResponse
    {
        [$run] = $this->findRun($runId);
        $failure = collect($run->steps[(string) $request->input('step')]['failures'] ?? [])
            ->firstWhere('item', $request->integer('item'));

        abort_if($failure === null, 404, 'Import run item not found.');

        return new JsonResponse([
            'previewHtml' => template('assets/_previews/text', [
                'language' => 'json',
                'contents' => Html::encode((string) Json::encode($failure['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            ]),
        ]);
    }

    /**
     * @return array{ImportRun, ImportPlanData}
     */
    private function findRun(string $runId): array
    {
        $run = ImportRun::query()->where('uid', $runId)->with('user')->first();

        abort_if($run === null, 404, 'Import run not found.');

        $importPlan = $run->planUid !== null
            ? $this->importPlans->getImportPlanByUid($run->planUid)
            : $this->importPlans->getImportPlanByHandle($run->planHandle);

        abort_if($importPlan === null, 404, 'Import plan not found.');

        return [$run, $importPlan];
    }

    /**
     * A run as the list of runs shows it, with the plan it's a run of.
     *
     * @return array<string, mixed>
     */
    private function runRow(ImportRun $run, ?ImportPlanData $importPlan): array
    {
        // only a plan's latest run can be unfinished, and once nothing's running it never will be
        $interrupted = ! $run->status->isFinished() && ($importPlan === null || ! $this->import->isRunning($importPlan));

        return [
            ...$this->importRuns->toArray($run, $interrupted, withFailures: false),
            'plan' => [
                'name' => (string) ($importPlan->name ?? $run->planHandle),
                'url' => $importPlan?->isEditable() ? route('craft.cp.import.edit', ['handle' => $importPlan->handle]) : null,
            ],
        ];
    }

    /**
     * The plan's steps that its items can still be previewed against, by UID.
     *
     * @return array<string, array<string, mixed>>
     */
    private function previewSteps(ImportPlanData $importPlan): array
    {
        $steps = [];

        foreach ($importPlan->steps ?? [] as $step) {
            if ($step instanceof ElementImporter && $step->uid !== null) {
                $steps[$step->uid] = $step->toArrayData();
            }
        }

        return $steps;
    }

    /**
     * The failed items each step would now skip, by step uid, as there'd be nothing to preview for them.
     *
     * @return array<string, list<int>> The items' numbers, from 1.
     */
    private function skippedInPreview(ImportRun $run, ImportPlanData $importPlan): array
    {
        $skipped = [];

        foreach ($importPlan->steps ?? [] as $step) {
            if (! $step instanceof ElementImporter || empty($run->steps[$step->uid]['failures'])) {
                continue;
            }

            try {
                $items = $this->importPreviews->previewStep($step, result: ItemPreviewResult::Skip, pageSize: PHP_INT_MAX)['items'];
            } catch (Throwable) {
                continue;
            }

            $failed = array_column($run->steps[$step->uid]['failures'], 'item');
            $skipped[$step->uid] = array_values(array_intersect(
                array_map(fn (array $item): int => $item['index'] + 1, $items),
                $failed,
            ));
        }

        return $skipped;
    }

    /**
     * A page of a step run's items, of one outcome or all of them, as the preview lists a step's items, with how many
     * came to each. A run from before its items were recorded can only list the failed ones it kept.
     *
     * @return array{items: list<array<string, mixed>>, result: string, results: list<string>, counts: array<string, int>, total: int, matching: int, page: int, pageSize: int, matchColumns: list<array{key: string, label: string}>}
     */
    private function stepItems(ImportRun $run, ImportPlanData $importPlan, string $stepUid, ?ImportItemOutcome $outcome, ?CraftUser $viewer, int $page = 0): array
    {
        $pageSize = ImportPreviews::PAGE_SIZE;
        $stepRun = $run->steps[$stepUid];
        $failures = collect($stepRun['failures'] ?? [])->keyBy('item');
        $recorded = ImportRunItem::query()->where('runId', $run->id)->where('stepUid', $stepUid);
        $isRecorded = (clone $recorded)->exists();
        $listable = $isRecorded
            ? array_values(array_filter(array_keys(self::OUTCOME_RESULTS), fn (string $value): bool => ($stepRun['counts'][$value] ?? 0) > 0))
            : ($failures->isNotEmpty() ? [ImportItemOutcome::Failed->value] : []);

        $counts = array_fill_keys(array_column(ItemPreviewResult::cases(), 'value'), 0);

        foreach (self::OUTCOME_RESULTS as $value => $itemResult) {
            $counts[$itemResult->value] += $stepRun['counts'][$value] ?? 0;
        }

        [$matchKeys, $destinationLabels] = $this->matchKeys($importPlan, $stepUid);

        if ($isRecorded) {
            $query = $outcome !== null ? (clone $recorded)->where('outcome', $outcome->value) : $recorded;
            $matching = (clone $query)->count();
            $page = max(0, min($page, (int) ceil($matching / $pageSize) - 1));
            $rows = $query
                ->orderBy('item')
                ->offset($page * $pageSize)
                ->limit($pageSize)
                ->get()
                ->map(fn (ImportRunItem $item): array => [
                    'item' => $item->item,
                    'outcome' => $item->outcome,
                    'elementId' => $item->elementId,
                    'failure' => $failures->get($item->item),
                ])
                ->all();
        } else {
            $kept = $outcome === null || $outcome === ImportItemOutcome::Failed ? $failures->values()->all() : [];
            $matching = count($kept);
            $page = max(0, min($page, (int) ceil($matching / $pageSize) - 1));
            $rows = array_map(fn (array $failure): array => [
                'item' => $failure['item'],
                'outcome' => ImportItemOutcome::Failed,
                'elementId' => $failure['elementId'],
                'failure' => $failure,
            ], array_slice($kept, $page * $pageSize, $pageSize));
        }

        return [
            'items' => array_map(function (array $row) use ($viewer, $matchKeys, $destinationLabels): array {
                $element = $row['elementId'] !== null ? Elements::getElementById($row['elementId']) : null;
                $failure = $row['failure'];

                return [
                    'id' => "item:{$row['item']}",
                    'item' => $row['item'],
                    'result' => self::OUTCOME_RESULTS[$row['outcome']->value]->value,
                    'title' => $failure['title'] ?? ($element !== null ? ImportPreviews::itemTitle($element) : null),
                    'match' => $failure !== null
                        ? array_column($failure['match'] ?? [], 'value', 'label')
                        : ($element !== null && $matchKeys !== [] ? array_column(ImportHelper::matchedValues($element, $matchKeys, $destinationLabels), 'value', 'label') : []),
                    'url' => $this->editUrl($element, $viewer),
                    'failure' => $failure,
                ];
            }, $rows),
            'result' => $outcome !== null ? self::OUTCOME_RESULTS[$outcome->value]->value : 'all',
            'results' => array_map(fn (string $value): string => self::OUTCOME_RESULTS[$value]->value, $listable),
            'counts' => $counts,
            'total' => array_sum($counts),
            'matching' => $matching,
            'page' => $page,
            'pageSize' => $pageSize,
            'matchColumns' => array_values(array_map(
                fn (string $label): array => ['key' => $label, 'label' => $label],
                array_unique([
                    ...array_map(fn (string $key): string => $destinationLabels[$key]['label'] ?? $key, $matchKeys),
                    ...array_merge([], ...array_map(fn (array $failure): array => array_column($failure['match'] ?? [], 'label'), $failures->values()->all())),
                ]),
            )),
        ];
    }

    /**
     * The keys a step matches items on, and its destinations' labels.
     *
     * @return array{list<string>, array<string, array{label: string}>}
     */
    private function matchKeys(ImportPlanData $importPlan, string $stepUid): array
    {
        $step = collect($importPlan->steps ?? [])->firstWhere('uid', $stepUid);

        if (! $step instanceof ElementImporter) {
            return [[], []];
        }

        try {
            $step->prepareForImport();
            $keys = array_map(strval(...), array_keys(ImportHelper::normalizeMatchCriteriaFromImporterConfig($step)));

            return [$keys, $keys !== [] ? $step->getDestinationLabels() : []];
        } catch (Throwable) {
            return [[], []];
        }
    }

    private function editUrl(?ElementInterface $element, ?CraftUser $viewer): ?string
    {
        return $element !== null && $viewer?->can('view', $element) ? $element->getCpEditUrl() : null;
    }
}
