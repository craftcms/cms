<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\Workflows;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Workflow\Data\WorkflowDraftReviewData;
use CraftCms\Cms\Workflow\Data\WorkflowReviewData;
use Illuminate\Support\Uri;
use Override;

use function CraftCms\Cms\currentUser;

/**
 * The Inertia payload for the entry edit screen (`content/Edit`).
 */
class EntryEditViewModel extends ElementEditViewModel
{
    public function __construct(
        private readonly Entry $entry,
        ElementRequest $request,
        bool $canSave = true,
        bool $mergedCanonicalChanges = false,
    ) {
        parent::__construct($entry, $request, $canSave, $mergedCanonicalChanges);
    }

    #[Override]
    protected function elementSaveUrl(): string
    {
        return Url::actionUrl('entries/save-entry');
    }

    #[Override]
    public function activityTimelineUrl(): ?string
    {
        return $this->entry->getCanonical(true)->id
            ? Url::actionUrl('elements/activity')
            : null;
    }

    #[Override]
    public function activityPageUrl(): ?string
    {
        $canonical = $this->entry->getCanonical(true);

        if (! $canonical->id || ! $canonical->sectionId) {
            return null;
        }

        $url = Uri::of($canonical->getCpEditUrl());

        return $url->withPath("{$url->path()}/activity")->value();
    }

    public function sectionHandle(): ?string
    {
        return $this->entry->getSection()?->handle;
    }

    public function entryTypeId(): ?int
    {
        return $this->entry->typeId;
    }

    /**
     * @return array{
     *     convertedToDraft: bool,
     *     current: WorkflowReviewData|null,
     *     draftReviews: list<WorkflowDraftReviewData>
     * }
     */
    #[Override]
    public function workflow(): array
    {
        return [
            ...parent::workflow(),
            'draftReviews' => $this->draftReviews(),
        ];
    }

    /**
     * Returns review summaries for visible named drafts of the canonical entry.
     *
     * @return list<WorkflowDraftReviewData>
     */
    private function draftReviews(): array
    {
        if (($this->entry->getIsDraft() && ! $this->entry->isProvisionalDraft) || $this->entry->getIsRevision()) {
            return [];
        }

        $viewer = currentUser();
        $canonical = $this->entry->getCanonical(true);

        if ($viewer === null || ! $canonical->id) {
            return [];
        }

        return $canonical::find()
            ->draftOf($canonical)
            ->siteId($canonical->siteId)
            ->status(null)
            ->orderByDesc('dateUpdated')
            ->get()
            ->map(function (ElementInterface $draft) use ($viewer): ?WorkflowDraftReviewData {
                if (! $draft instanceof Entry || $draft->isProvisionalDraft || ! $viewer->can('view', $draft)) {
                    return null;
                }

                $review = Workflows::reviewData($draft, $viewer);

                if ($review === null) {
                    return null;
                }

                $currentRun = collect($review->runs)->firstWhere('current', true);
                if ($currentRun === null || $review->currentStage === null) {
                    return null;
                }

                $stage = $currentRun->stages[$review->currentStage];
                $submission = $currentRun->submission;

                return new WorkflowDraftReviewData(
                    name: (string) $draft->draftName,
                    requester: $submission->actor['label'],
                    stage: $stage->name,
                    statusLabel: $review->statusLabel,
                    statusIndicator: $review->statusIndicator,
                    url: Uri::of($draft->getCpEditUrl())->withFragment('workflow')->value(),
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The canonical id the save action expects, so an edit of a draft or a
     * revision posts against the entry it derives from rather than its own id.
     */
    public function saveId(): ?int
    {
        return $this->entry->getIsDraft() || $this->entry->getIsRevision()
            ? $this->entry->getCanonicalId()
            : $this->entry->id;
    }
}
