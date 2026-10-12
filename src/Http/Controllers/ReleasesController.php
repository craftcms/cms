<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\TableRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ReleaseEditViewModel;
use CraftCms\Cms\Http\ViewModels\ReleaseViewModel;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Controls\DateTime;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Table as UiTable;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ReleasesController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly Releases $releases,
    ) {}

    public function index(TableRequest $request): CpScreenResponse
    {
        $canManage = Gate::check('manageReleases');
        $searchTerm = trim($request->search() ?? '');

        $paginator = Release::query()
            ->withCount('items')
            ->when($searchTerm !== '', fn ($query) => $query->where('name', 'like', "%{$searchTerm}%"))
            ->when($request->input('sort.0.field') === 'name', fn ($query) => $query->orderBy(
                'name',
                $request->input('sort.0.direction') === 'desc' ? 'desc' : 'asc',
            ))
            ->orderByDesc('dateCreated')
            ->orderByDesc('id')
            ->paginate($request->limit(), pageName: Cms::config()->getPageTriggerParam(), page: $request->page())
            ->appends($request->except(Cms::config()->getPageTriggerParam()));

        $formatter = new IntlDateFormatter(app()->getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, Cms::timezone());
        $rows = $paginator->getCollection()->map(function (Release $release) use ($formatter): array {
            $date = $release->publishedAt ?? $release->plannedAt;
            $canDelete = $release->status->isMutable() && Gate::allows('manage', $release);

            return [
                'id' => $release->id,
                'name' => ['label' => $release->name, 'url' => route('craft.cp.releases.show', $release)],
                'status' => ['html' => Html::tag('craft-badge', t(ucfirst($release->status->value)), [
                    'fill' => match ($release->status) {
                        ReleaseStatus::Draft => Color::Gray->value,
                        ReleaseStatus::Scheduled, ReleaseStatus::Publishing => Color::Sky->value,
                        ReleaseStatus::Published => Color::Emerald->value,
                        ReleaseStatus::Failed => Color::Red->value,
                    },
                ])],
                'date' => $date === null ? t('Not set') : ['html' => Html::tag('time',
                    Html::encode($formatter->format($date) ?: t('Not set')),
                    ['datetime' => $date->toIso8601String()],
                )],
                'itemCount' => (int) $release->items_count,
                '_deletable' => $canDelete,
                '_deleteUrl' => $canDelete ? route('craft.cp.releases.destroy', $release) : null,
                '_deleteLabel' => t('Delete {name}', ['name' => $release->name]),
                '_deleteConfirmMessage' => t('Delete “{name}”? Its unpublished drafts will be preserved.', ['name' => $release->name]),
            ];
        });

        $table = UiTable::make('releases')
            ->columns([
                ['key' => 'name', 'label' => t('Name'), 'sortable' => true],
                ['key' => 'status', 'label' => t('Status')],
                ['key' => 'date', 'label' => t('Date')],
                ['key' => 'itemCount', 'label' => t('Entries')],
            ])
            ->rows($rows)
            ->perPage($paginator->perPage())
            ->pagination(Arr::only($paginator->toArray(), [
                'total', 'per_page', 'current_page', 'last_page',
                'next_page_url', 'prev_page_url', 'from', 'to',
            ]))
            ->searchable()
            ->emptyMessage(t('No releases exist yet.'))
            ->when($canManage, fn (UiTable $table) => $table
                ->deletable()
                ->createAction(t('New release'), route('craft.cp.releases.create'))
                ->createActionInPageHeader());

        return new CpScreenResponse()
            ->title(t('Releases'))
            ->ui(Ui::make([$table]));
    }

    public function create(): InertiaResponse
    {
        Gate::authorize('manageReleases');

        return Inertia::render('Ui', [
            'title' => t('Create a release'),
            'crumbs' => [
                new ActionItem()->label(t('Releases'))->href(route('craft.cp.releases.index')),
                new ActionItem()->label(t('Create a release')),
            ],
            app(ReleaseEditViewModel::class, ['release' => null]),
        ]);
    }

    public function show(Release $release, Request $request): InertiaResponse
    {
        $viewModel = app(ReleaseViewModel::class, [
            'release' => $release,
            'actor' => $request->craftUser(),
            'releases' => $this->releases,
        ]);

        return Inertia::render('releases/Show', [
            'title' => $release->name,
            'crumbs' => [
                new ActionItem()->label(t('Releases'))->href(route('craft.cp.releases.index')),
                new ActionItem()->label($release->name),
            ],
            $viewModel,
            'release' => Inertia::always($viewModel->release(...)),
            'readiness' => Inertia::always($viewModel->readiness(...)),
            'readOnly' => Inertia::always($viewModel->readOnly(...)),
            'showReadOnlyNotice' => false,
            'formAdditionalButtons' => Inertia::always($viewModel->formAdditionalButtons(...)),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validateReleaseData($request);

        $release = $this->releases->create(
            $data['name'],
            $this->dateTime($data['plannedAt'] ?? null),
            $request->craftUser(),
            $data['description'] ?? null,
        );

        return $this->asSuccess(
            t('Release created.'),
            data: [
                'release' => $release->toArray(),
            ],
            redirect: route('craft.cp.releases.show', $release),
        );
    }

    public function update(Release $release, Request $request): Response
    {
        $data = $this->validateReleaseData($request);

        $this->releases->update(
            $release,
            $data['name'],
            $this->dateTime($data['plannedAt'] ?? null),
            $request->craftUser(),
            $data['description'] ?? null,
        );

        return $this->asSuccess(t('Release saved.'));
    }

    public function destroy(Release $release, Request $request): Response
    {
        $this->releases->delete($release, $request->craftUser());

        return $this->asSuccess(
            t('Release deleted.'),
            redirect: route('craft.cp.releases.index'),
        );
    }

    public function validateRelease(Release $release, Request $request): Response
    {
        $readiness = $this->releases->checkReadiness($release, $request->craftUser());

        return $this->asSuccess(t('Release checked.'), ['readiness' => $readiness->toArray()]);
    }

    public function scheduleModal(Release $release, UiResolver $uiResolver): JsonResponse
    {
        Gate::authorize('publish', $release);

        $date = $release->publishedAt ?? $release->plannedAt;
        $ui = $uiResolver->resolve(Ui::make([
            Field::make(t('Publication date'), DateTime::make('publishAt')->showTime()->showTimeZone())
                ->fieldset()
                ->required(),
        ]), new UiContext(values: [
            'publishAt' => [
                'date' => $date?->format('Y-m-d') ?? '',
                'time' => $date?->format('H:i') ?? '',
                'timezone' => $date?->getTimezone()->getName() ?? Cms::timezone(),
            ],
        ]));

        return new JsonResponse([
            'ui' => $ui,
            'title' => t('Schedule release'),
            'submitLabel' => t('Schedule'),
        ]);
    }

    public function schedule(Release $release, Request $request): Response
    {
        $validated = $request->validate([
            'publishAt' => ['required', 'array'],
            'publishAt.date' => ['required', 'date_format:Y-m-d'],
            'publishAt.time' => ['required', 'date_format:H:i'],
            'publishAt.timezone' => ['nullable', 'timezone'],
        ]);

        $publishAt = $this->dateTime($validated['publishAt']);

        if ($publishAt === null) {
            throw ValidationException::withMessages(['publishAt' => t('Enter a valid publication date and time.')]);
        }

        $this->releases->schedule($release, $publishAt, $request->craftUser());

        return $this->asSuccess(t('Release scheduled.'));
    }

    public function unschedule(Release $release, Request $request): Response
    {
        $this->releases->unschedule($release, $request->craftUser());

        return $this->asSuccess(t('Release unscheduled.'));
    }

    public function publish(Release $release, Request $request): Response
    {
        $this->releases->publish($release, $request->craftUser());

        return $this->asSuccess(t('Release published.'));
    }

    public function attach(Release $release, Request $request): Response
    {
        $validated = $request->validate([
            'draftId' => ['required', 'integer'],
            'siteId' => ['nullable', 'integer'],
        ]);

        $draftElement = $this->draftElement((int) $validated['draftId'], isset($validated['siteId']) ? (int) $validated['siteId'] : null);

        $this->releases->attach($release, $draftElement, $request->craftUser());

        return $this->asSuccess(
            t('Draft added to “{name}”.', ['name' => $release->name]),
            data: ['draftId' => $draftElement->draftId],
            redirect: $draftElement->getCpEditUrl(),
        );
    }

    public function detach(Release $release, int $draft, Request $request): Response
    {
        $this->releases->detach($release, $draft, $request->craftUser());

        return $this->asSuccess(t('Draft removed from the release.'));
    }

    /** @return array{name: string, description?: string|null, plannedAt?: array{date?: string, time?: string, timezone?: string}|null} */
    private function validateReleaseData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'plannedAt' => ['nullable', 'array'],
            'plannedAt.date' => ['nullable', 'date_format:Y-m-d'],
            'plannedAt.time' => ['nullable', 'date_format:H:i'],
            'plannedAt.timezone' => ['nullable', 'timezone'],
        ]);
    }

    /** @param array{date?: string, time?: string, timezone?: string}|null $value */
    private function dateTime(?array $value): ?\DateTimeInterface
    {
        if (($value['date'] ?? '') === '') {
            return null;
        }

        return DateTimeHelper::toDateTime([
            'datetime' => trim(sprintf('%s %s', $value['date'], $value['time'] ?? '00:00')),
            'timezone' => $value['timezone'] ?? null,
        ]) ?: null;
    }

    private function draftElement(int $draftId, ?int $siteId): ElementInterface
    {
        $elementType = DB::table(Table::ELEMENTS)->where('draftId', $draftId)->value('type');
        abort_unless(is_string($elementType) && is_a($elementType, ElementInterface::class, true), 404);

        $draftElement = $elementType::find()
            ->draftId($draftId)
            ->provisionalDrafts(false)
            ->siteId($siteId ?? '*')
            ->status(null)
            ->one();

        abort_unless($draftElement instanceof ElementInterface, 404);

        return $draftElement;
    }
}
