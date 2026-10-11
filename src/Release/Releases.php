<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release;

use Closure;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Release\Data\ReleaseReadiness;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Events\ReleasePublished;
use CraftCms\Cms\Release\Exceptions\ReleaseException;
use CraftCms\Cms\Release\Exceptions\ReleaseNotDue;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Models\ReleaseItem;
use CraftCms\Cms\Release\Notifications\ReleaseFailed;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\Workflows;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\ConcurrencyErrorDetector;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
#[Singleton]
class Releases
{
    /** @var array<int, int> */
    private array $mutableDrafts = [];

    public function __construct(
        private readonly Workflows $workflows,
        private readonly ConcurrencyErrorDetector $concurrencyErrorDetector,
    ) {}

    public function create(string $name, ?DateTimeInterface $plannedAt, CraftUser $actor, ?string $description = null): Release
    {
        abort_unless($actor->can('manageReleases'), Response::HTTP_FORBIDDEN, t('You are not allowed to manage releases.'));

        return Release::query()->create([
            'name' => $name,
            'description' => $description,
            'plannedAt' => $plannedAt,
            'status' => ReleaseStatus::Draft,
            'createdById' => $actor->getCraftUserId(),
            'uid' => Str::uuid()->toString(),
        ]);
    }

    public function update(Release $release, string $name, ?DateTimeInterface $plannedAt, CraftUser $actor, ?string $description = null): Release
    {
        abort_unless($actor->can('manageReleases'), Response::HTTP_FORBIDDEN, t('You are not allowed to manage releases.'));

        return DB::transaction(function () use ($release, $name, $plannedAt, $description): Release {
            $release = $this->lockRelease($release->id);
            $this->ensureMutable($release);
            $release->update([
                'name' => $name,
                'description' => $description,
                'plannedAt' => $plannedAt,
            ]);

            return $release->refresh();
        });
    }

    public function delete(Release $release, CraftUser $actor): void
    {
        abort_unless($actor->can('manageReleases'), Response::HTTP_FORBIDDEN, t('You are not allowed to manage releases.'));

        DB::transaction(function () use ($release): void {
            $release = $this->lockRelease($release->id);
            $this->ensureMutable($release);
            $release->delete();
        });
    }

    public function attach(Release $release, ElementInterface $draft, CraftUser $actor): ReleaseItem
    {
        $draftId = $this->draftId($draft);

        return DB::transaction(function () use ($release, $draftId, $actor): ReleaseItem {
            $release = $this->lockRelease($release->id);
            $this->ensureMutable($release);
            Draft::lockIds($draftId);
            $this->ensurePersistedDraftIsSaved($draftId);
            $draft = $this->freshDraft($draftId);
            $this->ensureAttachableDraft($draft);
            $this->lockRootIds((int) $draft->getCanonicalId());

            if (! $actor->can('attach', [$release, $draft])) {
                throw new ReleaseException(t('You are not allowed to add this draft to the release.'), Response::HTTP_FORBIDDEN);
            }

            $this->ensureReadyWorkflow($draft, true);

            if ($this->releaseForDraft($draft) !== null) {
                throw new ReleaseException(t('This draft already belongs to an active release.'));
            }

            if ($release->items()->where('canonicalId', $draft->getCanonicalId())->exists()) {
                throw new ReleaseException(t('This release already contains a draft for that element.'));
            }

            return $release->items()->create([
                'draftId' => $draft->draftId,
                'canonicalId' => $draft->getCanonicalId(),
            ]);
        });
    }

    public function detach(Release $release, ElementInterface|int $draft, CraftUser $actor): void
    {
        $draftId = $this->draftId($draft);

        DB::transaction(function () use ($release, $draftId, $actor): void {
            $release = $this->lockRelease($release->id);
            $this->ensureMutable($release);
            Draft::lockIds($draftId);
            $draft = $this->freshDraft($draftId);

            if (! $actor->can('detach', [$release, $draft])) {
                throw new ReleaseException(t('You are not allowed to remove this draft from the release.'), Response::HTTP_FORBIDDEN);
            }

            $deleted = $release->items()->where('draftId', $draftId)->delete();
            if ($deleted !== 1) {
                throw new ReleaseException(t('The draft is not attached to this release.'));
            }
        });
    }

    public function releaseForDraft(ElementInterface|int $draft): ?Release
    {
        $draftId = $this->draftId($draft);

        return Release::query()
            ->where('status', '!=', ReleaseStatus::Published)
            ->whereHas('items', fn (Builder $query) => $query->where('draftId', $draftId))
            ->first();
    }

    public function isDraftAttached(ElementInterface|int $draft): bool
    {
        return $this->releaseForDraft($draft) !== null;
    }

    public function assertDraftMutable(ElementInterface|int $draft): void
    {
        $draftId = $this->draftId($draft);
        if (($this->mutableDrafts[$draftId] ?? 0) > 0) {
            return;
        }

        if ($this->isDraftAttached($draftId)) {
            throw new ReleaseException(t('This draft is attached to a release and cannot be changed.'));
        }
    }

    public function assertElementMutable(ElementInterface $element): void
    {
        foreach ($this->owningDraftIds($element) as $draftId) {
            $this->assertDraftMutable($draftId);
        }
    }

    public function lockAndAssertElementsMutable(ElementInterface ...$elements): void
    {
        $draftIds = collect($elements)
            ->flatMap(fn (ElementInterface $element): array => $this->owningDraftIds($element))
            ->unique()
            ->sort()
            ->values()
            ->all();

        Draft::lockIds(...$draftIds);
        $this->lockElementRoots($elements);

        foreach ($draftIds as $draftId) {
            $this->assertDraftMutable($draftId);
        }
    }

    /** @param list<ElementInterface> $elements */
    private function lockElementRoots(array $elements): void
    {
        $rootIds = [];

        foreach ($elements as $element) {
            $visited = [];

            while ($element instanceof NestedElementInterface) {
                $objectId = spl_object_id($element);
                if (isset($visited[$objectId])) {
                    break;
                }
                $visited[$objectId] = true;

                $owner = $element->getOwner();
                if ($owner === null) {
                    break;
                }

                $element = $owner;
            }

            if (is_int($element->id) && $element->id > 0) {
                $rootIds[] = $element->id;
            }
        }

        $this->lockRootIds(...$rootIds);
    }

    public function checkReadiness(Release $release, CraftUser $actor): ReleaseReadiness
    {
        if (! $actor->can('manageReleases') && ! $actor->can('publishReleases')) {
            throw new ReleaseException(t('You are not allowed to publish releases.'), Response::HTTP_FORBIDDEN);
        }

        return $this->buildReadiness($release);
    }

    public function publish(Release $release, CraftUser $actor): Release
    {
        return $this->publishRelease($release, false, $actor);
    }

    private function publishRelease(Release $release, bool $scheduledExecution, ?CraftUser $actor = null): Release
    {
        [$publishedRelease, $failedRelease, $publicationFailure] = DB::transaction(function () use ($release, $scheduledExecution, $actor): array {
            $release = $this->lockRelease($release->id);
            $allowedStatuses = $scheduledExecution
                ? [ReleaseStatus::Scheduled]
                : [ReleaseStatus::Draft, ReleaseStatus::Failed];

            if (! in_array($release->status, $allowedStatuses, true)) {
                if ($scheduledExecution) {
                    throw new ReleaseNotDue(t('The release is no longer scheduled.'));
                }

                throw new ReleaseException(t('This release cannot be published in its current state.'));
            }

            if ($scheduledExecution && ($release->publishedAt === null || $release->publishedAt->isFuture())) {
                throw new ReleaseNotDue(t('The release is not due yet.'));
            }

            $items = $this->lockReleaseDrafts($release);

            if ($actor !== null && ! $actor->can('publishReleases')) {
                throw new ReleaseException(t('You are not allowed to publish releases.'), Response::HTTP_FORBIDDEN);
            }

            try {
                $publishedRelease = DB::transaction(
                    fn (): Release => $this->executePublication($release, $items),
                );

                return [$publishedRelease, null, null];
            } catch (Throwable $exception) {
                $connection = DB::connection();
                $physicalTransactionActive = $connection->getPdo()->inTransaction();
                if ($this->concurrencyErrorDetector->causedByConcurrencyError($exception) || ! $physicalTransactionActive) {
                    if (! $physicalTransactionActive && $connection->transactionLevel() > 0) {
                        $connection->rollBack(0);
                    }

                    throw $exception;
                }

                $release->refresh();
                $release->update([
                    'status' => ReleaseStatus::Failed,
                    'lastError' => $exception->getMessage(),
                    'publishAttempts' => $release->publishAttempts + 1,
                ]);

                return [null, $release->refresh(), $exception];
            }
        }, 3);

        if ($publicationFailure instanceof Throwable) {
            $this->notifyPublicationFailure($failedRelease, $publicationFailure);

            throw $publicationFailure;
        }

        try {
            event(new ReleasePublished($publishedRelease));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $publishedRelease;
    }

    /** @param Collection<int, ReleaseItem> $items */
    private function executePublication(Release $release, Collection $items): Release
    {
        $this->lockRootIds(...$items->pluck('canonicalId')->map(fn ($id): int => (int) $id)->all());

        if ($items->isEmpty()) {
            throw new ReleaseException(t('The release has no drafts.'));
        }

        $release->update([
            'status' => ReleaseStatus::Publishing,
            'lastError' => null,
            'publishAttempts' => $release->publishAttempts + 1,
        ]);

        foreach ($items as $item) {
            if ($item->draftId === null) {
                throw new ReleaseException(t('The draft no longer exists.'));
            }

            $draft = $this->freshDraft((int) $item->draftId);
            if (! $draft::trackChanges() && ElementHelper::isOutdated($draft)) {
                throw new ReleaseException(t('The live element changed after this draft was prepared.'));
            }

            $canonical = $this->withMutableDraft(
                (int) $item->draftId,
                function () use ($draft): ElementInterface {
                    if ($draft::trackChanges() && ElementHelper::isOutdated($draft)) {
                        app(Elements::class)->mergeCanonicalChanges($draft);
                    }

                    $this->ensureReadyWorkflow($draft, true);

                    return $this->validateAndApplyDraft($draft);
                },
            );
            $item->update([
                'draftId' => null,
                'publishedElementId' => $canonical->id,
                'publishedAt' => now(),
            ]);
        }

        $release->update([
            'status' => ReleaseStatus::Published,
            'publishedAt' => now(),
            'lastError' => null,
        ]);

        return $release->refresh()->load('items');
    }

    private function buildReadiness(
        Release $release,
        bool $lockWorkflowRun = false,
    ): ReleaseReadiness {
        $errors = [];
        $warnings = [];
        $itemErrors = [];
        $itemWarnings = [];
        $items = $release->items()->orderBy('draftId')->get();

        if ($items->isEmpty()) {
            $errors[] = t('The release has no drafts.');
        }

        foreach ($items as $item) {
            if ($item->draftId === null) {
                $itemErrors[$item->id][] = t('The draft no longer exists.');

                continue;
            }

            try {
                $draft = $this->freshDraft((int) $item->draftId);
            } catch (ReleaseException $exception) {
                $itemErrors[$item->id][] = $exception->getMessage();

                continue;
            }

            if (ElementHelper::isOutdated($draft)) {
                if ($draft::trackChanges()) {
                    $itemWarnings[$item->id][] = t('Live changes will be merged into this draft before publication.');
                } else {
                    $itemErrors[$item->id][] = t('The live element changed after this draft was prepared.');
                }
            }

            try {
                $this->ensureReadyWorkflow($draft, $lockWorkflowRun);
            } catch (ReleaseException $exception) {
                $itemErrors[$item->id][] = $exception->getMessage();
            }

            $siteDrafts = $draft::find()
                ->draftId($draft->draftId)
                ->site('*')
                ->status(null)
                ->all();

            foreach ($siteDrafts as $siteDraft) {
                $candidate = clone $siteDraft;
                if ($siteDraft instanceof Entry && $candidate instanceof Entry) {
                    $candidate->setAuthorIds($siteDraft->getAuthorIds());
                }
                if ($candidate::trackChanges() && ElementHelper::isOutdated($candidate)) {
                    $candidate->mergeCanonicalChanges();
                }
                if ($candidate->enabled && $candidate->getEnabledForSite()) {
                    $candidate->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                }
                if (! $candidate->validate()) {
                    $itemErrors[$item->id] = [
                        ...($itemErrors[$item->id] ?? []),
                        ...$candidate->errors()->all(),
                    ];
                }
            }

            $target = $release->publishedAt ?? now();
            foreach ($siteDrafts as $siteDraft) {
                if (! $siteDraft instanceof Entry) {
                    continue;
                }
                if ($siteDraft->postDate !== null && $siteDraft->postDate > $target) {
                    $itemWarnings[$item->id][] = t('This entry will remain pending in at least one site at the release time.');
                }
                if ($siteDraft->expiryDate !== null && $siteDraft->expiryDate <= $target) {
                    $itemWarnings[$item->id][] = t('This entry will be expired in at least one site at the release time.');
                }
            }

            if ($this->hasUnpublishedRelationTargetsOutsideRelease($draft, $release)) {
                $itemWarnings[$item->id][] = t('This draft links to unpublished content that is not part of the release.');
            }
        }

        return new ReleaseReadiness(
            ready: $errors === [] && $itemErrors === [],
            errors: $errors,
            warnings: $warnings,
            itemErrors: $itemErrors,
            itemWarnings: $itemWarnings,
        );
    }

    private function hasUnpublishedRelationTargetsOutsideRelease(ElementInterface $draft, Release $release): bool
    {
        $sourceIds = $this->ownedElementIds([(int) $draft->id]);

        $targetIds = DB::table(Table::RELATIONS)
            ->whereIn('sourceId', $sourceIds)
            ->pluck('targetId')
            ->unique();

        if ($targetIds->isEmpty()) {
            return false;
        }

        $releaseItems = $release->items()->get(['draftId', 'canonicalId']);

        return DB::table(Table::ELEMENTS)
            ->whereIn('id', $targetIds)
            ->whereNotIn('id', $releaseItems->pluck('canonicalId'))
            ->where(function (Builder $query) {
                $query
                    ->where('enabled', false)
                    ->orWhere(function (Builder $query) {
                        $query->whereNotNull('draftId')->whereNull('canonicalId');
                    });
            })
            ->where(function (Builder $query) use ($releaseItems) {
                $draftIds = $releaseItems->pluck('draftId')->filter();
                if ($draftIds->isNotEmpty()) {
                    $query->whereNull('draftId')->orWhereNotIn('draftId', $draftIds);
                }
            })
            ->exists();
    }

    private function lockRootIds(int ...$rootIds): void
    {
        $rootIds = array_values(array_unique($rootIds));
        sort($rootIds);

        if ($rootIds === []) {
            return;
        }

        DB::table(Table::ELEMENTS)
            ->whereIn('id', $rootIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    /**
     * @param  list<int>  $ownerIds
     * @return list<int>
     */
    private function ownedElementIds(array $ownerIds): array
    {
        $elementIds = $ownerIds;
        $unresolvedOwnerIds = $ownerIds;

        while ($unresolvedOwnerIds !== []) {
            $nestedIds = DB::table(Table::ELEMENTS_OWNERS)
                ->whereIn('ownerId', $unresolvedOwnerIds)
                ->orderBy('ownerId')
                ->orderBy('sortOrder')
                ->orderBy('elementId')
                ->pluck('elementId')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $unresolvedOwnerIds = array_values(array_diff($nestedIds, $elementIds));
            array_push($elementIds, ...$unresolvedOwnerIds);
        }

        sort($elementIds);

        return array_values(array_unique($elementIds));
    }

    private function ensureReadyWorkflow(ElementInterface $draft, bool $lockRun = false): ?ElementInterface
    {
        $approvalDrafts = array_values(array_filter(
            $this->localizedDrafts($draft),
            $this->workflows->requiresApproval(...),
        ));

        if ($approvalDrafts === []) {
            return null;
        }

        $run = $this->latestWorkflowRun($draft, $lockRun);
        if ($run?->status !== WorkflowStatus::Approved) {
            throw new ReleaseException(t('The draft must complete its approval workflow before it can be added or published.'));
        }

        return $approvalDrafts[0];
    }

    private function latestWorkflowRun(ElementInterface $draft, bool $lockForUpdate): ?WorkflowRun
    {
        if (! $lockForUpdate) {
            return $this->workflows->latestRun($draft);
        }

        return WorkflowRun::query()
            ->where('draftId', $draft->draftId)
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    private function ensureAttachableDraft(ElementInterface $draft): void
    {
        if (
            ! $draft::hasDrafts()
            || ($draft instanceof NestedElementInterface && $draft->getPrimaryOwnerId() !== null)
            || ! $draft->getIsDraft()
            || $draft->isProvisionalDraft
            || ! $draft->draftId
        ) {
            throw new ReleaseException(t('Only saved, named root drafts can be attached to a release.'));
        }
    }

    private function ensurePersistedDraftIsSaved(int $draftId): void
    {
        $saved = DB::table(Table::DRAFTS)
            ->where('id', $draftId)
            ->lockForUpdate()
            ->value('saved');

        if (! $saved) {
            throw new ReleaseException(t('Only saved, named root drafts can be attached to a release.'));
        }
    }

    private function ensureMutable(Release $release): void
    {
        if (! $release->status->isMutable()) {
            throw new ReleaseException(t('Scheduled and completed releases cannot be changed.'));
        }
    }

    private function lockRelease(int $releaseId): Release
    {
        return Release::query()->lockForUpdate()->find($releaseId)
            ?? throw new ReleaseException(t('The release no longer exists.'));
    }

    private function freshDraft(int $draftId): ElementInterface
    {
        return Draft::findElement($draftId) ?? throw new ReleaseException(t('The release draft no longer exists.'));
    }

    private function validateAndApplyDraft(ElementInterface $draft): ElementInterface
    {
        $workflowDraft = collect($this->localizedDrafts($draft))
            ->first($this->workflows->requiresApproval(...));
        $draftToApply = $workflowDraft !== null ? clone $workflowDraft : $draft;

        if ($draftToApply->enabled && $draftToApply->getEnabledForSite()) {
            $draftToApply->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
        }

        if ($draftToApply->getIsUnpublishedDraft()) {
            $draftToApply->propagateRequired = true;
        }

        $draftToApply->applyingDraft = true;
        $saved = app(Elements::class)->saveElement(
            element: $draftToApply,
            crossSiteValidate: Sites::isMultiSite(),
        );

        if (! $saved) {
            throw new ReleaseException(
                implode(' ', $draftToApply->errors()->all()) ?: t('The draft could not be validated for publication.'),
            );
        }

        $draftToApply->applyingDraft = false;
        $draftToApply->propagateRequired = false;

        return app(Drafts::class)->applyDraft($draftToApply);
    }

    /** @return list<ElementInterface> */
    private function localizedDrafts(ElementInterface $draft): array
    {
        if (! is_int($draft->draftId) || $draft->draftId < 1) {
            return [$draft];
        }

        return $draft::find()
            ->draftId($draft->draftId)
            ->site('*')
            ->status(null)
            ->all();
    }

    /** @return list<int> */
    private function owningDraftIds(ElementInterface $element): array
    {
        $draftIds = [];
        $visited = [];

        while (true) {
            $objectId = spl_object_id($element);
            if (isset($visited[$objectId])) {
                break;
            }
            $visited[$objectId] = true;

            if ($element->getIsDraft() && $element->draftId) {
                $draftIds[] = (int) $element->draftId;
            }

            if (! $element instanceof NestedElementInterface) {
                break;
            }

            $owner = $element->getOwner();
            if ($owner === null) {
                break;
            }

            $element = $owner;
        }

        return array_values(array_unique($draftIds));
    }

    /** @return Collection<int, ReleaseItem> */
    private function lockReleaseDrafts(Release $release): Collection
    {
        $items = ReleaseItem::query()
            ->where('releaseId', $release->id)
            ->orderBy('draftId')
            ->lockForUpdate()
            ->get();
        $draftIds = $items->pluck('draftId')->filter()->map(fn ($id): int => (int) $id)->all();
        Draft::lockIds(...$draftIds);

        return $items;
    }

    private function notifyPublicationFailure(Release $release, Throwable $exception): void
    {
        try {
            Log::error("Release {$release->id} failed to publish: {$exception->getMessage()}", [
                'releaseId' => $release->id,
                'exception' => $exception,
            ]);
        } catch (Throwable $loggingException) {
            report($loggingException);
        }

        try {
            $managers = User::find()
                ->status(User::STATUS_ACTIVE)
                ->collect()
                ->filter(fn (User $user): bool => $user->can('manageReleases'));

            Notification::send($managers, new ReleaseFailed($release->name, $exception->getMessage()));
        } catch (Throwable $notificationException) {
            report($notificationException);
        }
    }

    private function draftId(ElementInterface|int $draft): int
    {
        $draftId = $draft instanceof ElementInterface ? $draft->draftId : $draft;
        if (! is_int($draftId) || $draftId < 1) {
            throw new ReleaseException(t('A saved draft is required.'));
        }

        return $draftId;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function withMutableDraft(int $draftId, Closure $callback): mixed
    {
        $this->mutableDrafts[$draftId] = ($this->mutableDrafts[$draftId] ?? 0) + 1;

        try {
            return $callback();
        } finally {
            $this->mutableDrafts[$draftId]--;
            if ($this->mutableDrafts[$draftId] === 0) {
                unset($this->mutableDrafts[$draftId]);
            }
        }
    }
}
