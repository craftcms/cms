<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Exceptions\ReleaseException;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Models\ReleaseItem;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use CraftCms\Cms\Workflow\Workflows;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\ConcurrencyErrorDetector;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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

        foreach ($approvalDrafts as $approvalDraft) {
            if (! ElementHelper::isOutdated($approvalDraft)) {
                continue;
            }

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

    private function draftId(ElementInterface|int $draft): int
    {
        $draftId = $draft instanceof ElementInterface ? $draft->draftId : $draft;
        if (! is_int($draftId) || $draftId < 1) {
            throw new ReleaseException(t('A saved draft is required.'));
        }

        return $draftId;
    }
}
