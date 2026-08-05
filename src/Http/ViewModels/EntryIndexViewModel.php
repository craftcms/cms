<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Section\Resources\SectionResource;
use CraftCms\Cms\Support\Facades\Sections;
use Override;

/**
 * The Inertia payload for the entry index screen (`content/Index`).
 */
class EntryIndexViewModel extends ContentIndexViewModel
{
    public function __construct(
        ElementIndexRequest $request,
        ?string $page = null,
        private readonly ?string $sectionHandle = null,
    ) {
        parent::__construct(Entry::class, $request, $page);
    }

    public function sectionHandle(): string
    {
        return $this->sectionHandle ?? '';
    }

    /** @return list<array<string, mixed>> */
    public function publishableSections(): array
    {
        return SectionResource::collection(Sections::getPublishableSections())->resolve();
    }

    /**
     * Maps the section-handle route segment (e.g. `content/entries/blog`,
     * `content/entries/singles`) to its source key (`singles` for Single
     * sections, `section:{uid}` otherwise).
     */
    #[Override]
    protected function defaultSourceKey(): ?string
    {
        if ($this->sectionHandle === null || $this->sectionHandle === '') {
            return null;
        }

        if ($this->sectionHandle === 'singles') {
            return 'singles';
        }

        $section = Sections::getSectionByHandle($this->sectionHandle);

        return $section ? "section:$section->uid" : null;
    }
}
