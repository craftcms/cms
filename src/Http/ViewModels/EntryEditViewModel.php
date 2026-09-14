<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Uri;
use Override;

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
