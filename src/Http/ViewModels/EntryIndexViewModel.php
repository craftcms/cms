<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Resources\SectionResource;
use CraftCms\Cms\Support\Facades\Sections;
use Override;

use function CraftCms\Cms\t;

/**
 * The Inertia payload for the entry index screen (`content/Index`).
 */
class EntryIndexViewModel extends ContentIndexViewModel
{
    /**
     * The legacy URL segment (and source key) for the combined Singles index.
     *
     * Singles are individual `section:{uid}` sources now, so this is no longer
     * a real source — see {@see sourceState()}.
     */
    private const string SINGLES_KEY = 'singles';

    /** @var array{0: ?string, 1: ?array<string, mixed>}|null */
    private ?array $resolvedSinglesSource = null;

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
     * Maps the section-handle route segment (e.g. `content/entries/blog`) to
     * its `section:{uid}` source key.
     *
     * `content/{page}/singles` predates per-single sources; it keeps working as
     * an index over every Single section through the {@see SINGLES_KEY}
     * pseudo-source that {@see sourceState()} resolves.
     */
    #[Override]
    protected function defaultSourceKey(): ?string
    {
        if ($this->sectionHandle === null || $this->sectionHandle === '') {
            return null;
        }

        if ($this->sectionHandle === self::SINGLES_KEY) {
            return self::SINGLES_KEY;
        }

        $section = Sections::getSectionByHandle($this->sectionHandle);

        return $section ? "section:$section->uid" : null;
    }

    /**
     * Resolves `singles` to a criteria-only pseudo-source spanning every
     * editable Single section, so bookmarks and stored links to
     * `content/{page}/singles` keep listing all singles on one page.
     *
     * @return array{0: ?string, 1: ?array<string, mixed>}
     */
    #[Override]
    protected function sourceState(): array
    {
        if ($this->resolvedSinglesSource !== null) {
            return $this->resolvedSinglesSource;
        }

        $requestedSource = $this->request->input('source') ?? $this->defaultSourceKey();

        if ($requestedSource !== self::SINGLES_KEY) {
            return parent::sourceState();
        }

        $sectionIds = Sections::getEditableSections()
            ->filter(fn (Section $section) => $section->type === SectionType::Single)
            ->map(fn (Section $section) => $section->id)
            ->values()
            ->all();

        // With no singles there's nothing to aggregate, so fall back to the
        // regular resolution, which lands on the first available source.
        if ($sectionIds === []) {
            return parent::sourceState();
        }

        return $this->resolvedSinglesSource = [self::SINGLES_KEY, [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => self::SINGLES_KEY,
            'label' => t('Singles'),
            'criteria' => [
                'sectionId' => $sectionIds,
                'editable' => true,
            ],
            'defaultSort' => ['title', 'asc'],
        ]];
    }
}
