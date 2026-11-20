<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Import\Data\ImportPlan;
use CraftCms\Cms\Import\Data\StepSummary;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportPreviews;
use CraftCms\Cms\Import\ImportRuns;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

use function CraftCms\Cms\template;

/**
 * An import run's page in each state it can be in, from made-up runs, so nothing is imported. Failed items are
 * previewed against the “Guides: with errors” plan when it's there.
 *
 * @phpstan-type StepRun array{label: string, status: string, progress: int, total: ?int, source: array{name: ?string, size: int}, counts: array<string, int>, failures: list<array<string, mixed>>}
 * @phpstan-type Run array{id: string, url: string, status: string, progress: int, trigger: ?string, user: ?string, dateCreated: string, dateStarted: ?string, dateFinished: ?string, steps: array<string, StepRun>}
 */
class ImportRunPagesController
{
    /** @var array<string, string> */
    public const array PAGES = [
        'queued' => 'Queued',
        'running' => 'Running: first step',
        'running-failures' => 'Running: with failures so far',
        'successful' => 'Successful: one step',
        'successful-steps' => 'Successful: three steps',
        'nothing' => 'Successful: nothing to import',
        'failed' => 'Failed: some items',
        'failed-many' => 'Failed: more failures than are kept',
        'failed-steps' => 'Failed: one of three steps',
        'interrupted' => 'Failed: interrupted',
    ];

    private const string ERRORS_PLAN = 'guidesWithErrors';

    /** Each outcome, and the result the preview gives an item that comes to it. */
    private const array OUTCOME_RESULTS = [
        'failed' => 'problem',
        'created' => 'new',
        'updated' => 'update',
        'unchanged' => 'unchanged',
        'skipped' => 'skip',
    ];

    private ?ImportPlan $plan = null;

    public function __invoke(string $page): InertiaResponse
    {
        $run = $this->run($page);
        $step = $this->planStep();

        return Inertia::render('import/Run', [
            'title' => 'Run of Guides: with errors',
            'crumbs' => [
                new ActionItem()->label('Import')->href(route('craft.cp.import.index')),
                new ActionItem()->label('Activity')->href(route('craft.cp.import.activity.index')),
            ],
            'run' => $run,
            // only the first step stands for the plan's, so only its items can be previewed
            'previewSteps' => (object) ($step ? [$step->uid => $step->toArrayData()] : []),
            // every step's settings are the plan's step's, so each card has some to show
            'stepSummaries' => (object) ($step ? array_fill_keys(array_keys($run['steps']), StepSummary::fromImporter($step)) : []),
            'skippedInPreview' => (object) [],
            'items' => (object) collect($run['steps'])
                ->filter(fn (array $step): bool => array_sum($step['counts']) > 0)
                ->map(fn (array $step): array => $this->stepItems($step, 'all'))
                ->all(),
        ]);
    }

    /** Stands in for a page of a run's items, of one result or all of them. */
    public function items(Request $request, string $runId): JsonResponse
    {
        $step = $this->run($this->page($runId))['steps'][(string) $request->input('step')] ?? abort(404);

        return new JsonResponse($this->stepItems($step, (string) $request->input('result', 'all'), $request->integer('page')));
    }

    /** Stands in for a failed item's data. */
    public function itemData(Request $request, string $runId): JsonResponse
    {
        $failure = collect($this->run($this->page($runId))['steps'][(string) $request->input('step')]['failures'] ?? [])
            ->firstWhere('item', $request->integer('item')) ?? abort(404);

        return new JsonResponse([
            'previewHtml' => template('assets/_previews/text', [
                'language' => 'json',
                'contents' => Html::encode((string) Json::encode($failure['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            ]),
        ]);
    }

    /** @return Run */
    private function run(string $page): array
    {
        $failures = $this->failures();

        return match ($page) {
            'queued' => $this->state($page, 'pending', 0, [
                $this->step('pending'),
                $this->step('pending', 'Entries (topics)'),
            ], started: null),
            'running' => $this->state($page, 'running', 12, [
                $this->step('running', progress: 36, total: 50, counts: ['created' => 14, 'updated' => 4]),
                $this->step('pending', 'Entries (topics)'),
                $this->step('pending', 'Entries (reviews)'),
            ], finished: null),
            'running-failures' => $this->state($page, 'running', 58, [
                $this->step('failed', progress: 100, total: 10, counts: ['created' => 4, 'updated' => 1, 'failed' => 5], failures: $failures),
                $this->step('running', 'Entries (topics)', progress: 72, total: 25, counts: ['created' => 12, 'unchanged' => 6]),
                $this->step('pending', 'Entries (reviews)'),
            ], finished: null),
            'successful' => $this->state($page, 'successful', 100, [
                $this->step('successful', total: 3, counts: ['updated' => 3]),
            ]),
            'successful-steps' => $this->state($page, 'successful', 100, [
                $this->step('successful', total: 50, counts: ['created' => 12, 'updated' => 30, 'unchanged' => 8]),
                $this->step('successful', 'Entries (topics)', total: 25, counts: ['unchanged' => 25]),
                $this->step('successful', 'Entries (reviews)', total: 40, counts: ['created' => 2, 'skipped' => 38]),
            ], trigger: 'cli'),
            'nothing' => $this->state($page, 'successful', 100, [
                $this->step('successful', total: 0),
            ], trigger: null, user: null),
            'failed' => $this->state($page, 'failed', 100, [
                $this->step('failed', total: 10, counts: ['created' => 4, 'updated' => 1, 'failed' => 5], failures: $failures),
            ]),
            'failed-many' => $this->state($page, 'failed', 100, [
                $this->step('failed', total: 400, counts: ['created' => 180, 'updated' => 60, 'failed' => 160], failures: $this->manyFailures(100)),
            ]),
            'failed-steps' => $this->state($page, 'failed', 100, [
                $this->step('successful', total: 50, counts: ['created' => 12, 'updated' => 38]),
                $this->step('failed', 'Entries (topics)', total: 10, counts: ['created' => 4, 'updated' => 1, 'failed' => 5], failures: $failures),
                $this->step('successful', 'Entries (reviews)', total: 40, counts: ['updated' => 40]),
            ]),
            'interrupted' => $this->state($page, 'failed', 42, [
                $this->step('successful', total: 50, counts: ['created' => 50]),
                $this->step('failed', 'Entries (topics)', progress: 28, total: 25, counts: ['created' => 7]),
                $this->step('pending', 'Entries (reviews)'),
            ]),
            default => abort(404),
        };
    }

    /**
     * @param  list<StepRun>  $steps
     * @return Run
     */
    private function state(
        string $page,
        string $status,
        int $progress,
        array $steps,
        ?string $trigger = 'cp',
        ?string $user = 'craftcms',
        ?int $started = 95,
        ?int $finished = 3,
    ): array {
        $now = Date::now();

        return [
            'id' => "workbench-$page",
            'url' => Url::cpUrl("workbench/import-run/$page"),
            'status' => $status,
            'progress' => $progress,
            'trigger' => $trigger,
            'user' => $user,
            'dateCreated' => $now->clone()->subSeconds(($started ?? 0) + 4)->toIso8601String(),
            'started' => ImportRuns::started($now->clone()->subSeconds(($started ?? 0) + 4)),
            'dateStarted' => $started !== null ? $now->clone()->subSeconds($started)->toIso8601String() : null,
            'dateFinished' => $started !== null && $finished !== null ? $now->clone()->subSeconds($finished)->toIso8601String() : null,
            'steps' => array_combine(array_slice($this->stepUids(), 0, count($steps)), $steps),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<array<string, mixed>>  $failures
     * @return StepRun
     */
    private function step(
        string $status,
        string $label = 'Entries',
        ?int $progress = null,
        ?int $total = null,
        array $counts = [],
        array $failures = [],
    ): array {
        return [
            'label' => $label,
            'status' => $status,
            'progress' => $progress ?? ($status === 'pending' ? 0 : 100),
            'total' => $status === 'pending' ? null : $total,
            'source' => ['name' => $status === 'pending' ? null : 'guides-with-errors.json', 'size' => 4210],
            'counts' => [...array_fill_keys(['created', 'updated', 'unchanged', 'skipped', 'failed'], 0), ...$counts],
            'failures' => $failures,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function failures(): array
    {
        $failure = fn (int $item, ?string $title, array $problems, array $data, ?string $message = null): array => [
            'item' => $item,
            'message' => $message ?? implode(' ', array_column($problems, 'message')),
            'elementId' => null,
            'title' => $title,
            'match' => $title !== null ? [['label' => 'Slug', 'value' => str($title)->slug()->toString()]] : [],
            'problems' => $problems,
            'data' => $data,
        ];

        return [
            $failure(3, null, [['path' => ['Title'], 'message' => 'Title cannot be blank.']], [
                'title' => '', 'summary' => 'Set up a staging site that mirrors production.', 'difficulty' => 'intermediate',
            ]),
            $failure(4, 'Translating static strings', [['path' => ['Difficulty'], 'message' => 'Difficulty must be one of: beginner, intermediate, advanced.']], [
                'title' => 'Translating static strings', 'difficulty' => 'expert',
            ]),
            $failure(5, 'Picking the right field type', [['path' => ['Contact email'], 'message' => 'Contact email is not a valid email address.']], [
                'title' => 'Picking the right field type', 'contactEmail' => 'editors at example dot com',
            ]),
            $failure(6, null, [], [
                'title' => 'Optimizing images on upload', 'reviewers' => ['nobody@example.com'],
            ], 'No user exists with the email “nobody@example.com”.'),
            $failure(9, null, [
                ['path' => ['Title'], 'message' => 'Title cannot be blank.'],
                ['path' => ['Difficulty'], 'message' => 'Difficulty must be one of: beginner, intermediate, advanced.'],
                ['path' => ['Content', 'Step group 1', 'Steps', 'Step 2', 'Minutes'], 'message' => 'Minutes must be a number.'],
            ], [
                'title' => '', 'difficulty' => 'guru',
            ]),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function manyFailures(int $count): array
    {
        return array_map(fn (int $i): array => [
            'item' => $i * 2,
            'message' => 'Title cannot be blank.',
            'elementId' => null,
            'title' => "Untitled guide $i",
            'match' => [['label' => 'Slug', 'value' => "untitled-guide-$i"]],
            'problems' => [['path' => ['Title'], 'message' => 'Title cannot be blank.']],
            'data' => ['title' => '', 'summary' => "Guide number $i."],
        ], range(1, $count));
    }

    /**
     * A page of a step run's items, of one result or all of them, as the run's page lists them. Failed ones are the
     * made-up failures; the rest stand for the workbench's guides, so their titles link somewhere.
     *
     * @param  StepRun  $step
     * @return array<string, mixed>
     */
    private function stepItems(array $step, string $result, int $page = 0): array
    {
        $pageSize = ImportPreviews::PAGE_SIZE;
        $counts = $step['counts'];
        $failures = collect($step['failures'])->keyBy('item');
        $guides = Entry::find()->section('guides')->status(null)->orderBy('title')->limit(50)->all();

        // each item's outcome, the failed ones at the numbers they were kept at and the rest around them
        $outcomes = [];
        $next = 1;

        foreach (array_keys(self::OUTCOME_RESULTS) as $outcome) {
            if ($outcome === 'failed') {
                continue;
            }

            for ($i = 0; $i < $counts[$outcome]; $i++) {
                while ($failures->has($next)) {
                    $next++;
                }

                $outcomes[$next++] = $outcome;
            }
        }

        foreach ($failures->keys() as $item) {
            $outcomes[$item] = 'failed';
        }

        ksort($outcomes);
        $outcome = array_search($result, self::OUTCOME_RESULTS, true);
        $listed = $outcome !== false ? array_filter($outcomes, fn (string $value): bool => $value === $outcome) : $outcomes;
        $matching = count($listed);
        $page = max(0, min($page, (int) ceil($matching / $pageSize) - 1));

        return [
            'items' => array_values(array_map(function (string $outcome, int $item) use ($failures, $guides): array {
                $failure = $failures->get($item);
                $guide = $failure === null && $outcome !== 'skipped' && $guides !== [] ? $guides[($item - 1) % count($guides)] : null;

                return [
                    'id' => "item:$item",
                    'item' => $item,
                    'result' => self::OUTCOME_RESULTS[$outcome],
                    'title' => $failure['title'] ?? $guide?->title,
                    'match' => $failure !== null ? array_column($failure['match'], 'value', 'label') : ($guide !== null ? ['Slug' => $guide->slug] : []),
                    'url' => $guide?->getCpEditUrl(),
                    'failure' => $failure,
                ];
            }, array_slice($listed, $page * $pageSize, $pageSize, true), array_keys(array_slice($listed, $page * $pageSize, $pageSize, true)))),
            'result' => $outcome !== false ? $result : 'all',
            'results' => array_values(array_map(fn (string $value): string => self::OUTCOME_RESULTS[$value], array_filter(array_keys(self::OUTCOME_RESULTS), fn (string $value): bool => $counts[$value] > 0))),
            'counts' => array_combine(array_values(self::OUTCOME_RESULTS), array_map(fn (string $value): int => $counts[$value], array_keys(self::OUTCOME_RESULTS))),
            'total' => array_sum($counts),
            'matching' => $matching,
            'page' => $page,
            'pageSize' => $pageSize,
            'matchColumns' => [['key' => 'Slug', 'label' => 'Slug']],
        ];
    }

    /** Whether a run's id is one of the made-up runs'. */
    public static function isMadeUp(string $runId): bool
    {
        return str_starts_with($runId, 'workbench-');
    }

    private function page(string $runId): string
    {
        return str_replace('workbench-', '', $runId);
    }

    /**
     * The made-up steps' uids, the first being the plan's own step when there is one.
     *
     * @return list<string>
     */
    private function stepUids(): array
    {
        return [$this->planStep()->uid ?? 'workbench-step-1', 'workbench-step-2', 'workbench-step-3'];
    }

    private function planStep(): ?ElementImporter
    {
        $this->plan ??= app(ImportPlans::class)->getImportPlanByHandle(self::ERRORS_PLAN);
        $step = collect($this->plan->steps ?? [])->first(fn ($step) => $step instanceof ElementImporter);

        return $step instanceof ElementImporter && $step->uid !== null ? $step : null;
    }
}
