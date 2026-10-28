<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Actions\MoveToSection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Resources\SectionResource;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Url;
use Override;

/**
 * The Inertia payload for the entry index screen (`content/Index`).
 *
 * @since 6.0.0
 */
class EntryIndexViewModel extends ContentIndexViewModel
{
    public const string MOVE_TO_SECTION_EVENT = 'entries:move-to-section';

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

    /** @return list<array<string, mixed>>|null */
    #[Override]
    public function actions(): ?array
    {
        $actions = parent::actions();

        if (! $actions) {
            return $actions;
        }

        return array_values(array_filter(array_map(
            $this->moveToSectionAction(...),
            $actions,
        )));
    }

    /** @return list<array<string, mixed>> */
    public function publishableSections(): array
    {
        return SectionResource::collection(Sections::getPublishableSections())->resolve();
    }

    /**
     * The entry index is a page of its own — `content/entries`, or
     * `content/{page}` for a custom index page.
     */
    #[Override]
    protected function indexUrl(): ?string
    {
        return Url::cpUrl($this->pageUri());
    }

    /**
     * Sections have index URLs of their own, which is what the rest of the CP
     * links them by — see {@see Section::getCpIndexUri()}, whose shape this
     * matches. Singles share one source, and so one `singles` URL.
     *
     * @param  array<string, mixed>  $source
     */
    #[Override]
    protected function sourceUrl(array $source): ?string
    {
        $handle = $source['key'] === 'singles'
            ? 'singles'
            : ($source['data']['handle'] ?? null);

        // Anything that isn't a section source (a custom source, say) has no
        // URL of its own, so fall back to the `?source=` query.
        return $handle === null
            ? parent::sourceUrl($source)
            : Url::cpUrl(sprintf('%s/%s', $this->pageUri(), $handle));
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

    /**
     * Whether the user can move each entry to another section, for the Move to
     * action's `selectionAttribute`.
     *
     * @return array<string, bool>
     */
    #[Override]
    protected function extraCapabilities(ElementInterface $element): array
    {
        return $element instanceof Entry ? ['movable' => $element->canMove()] : [];
    }

    /**
     * Points the Move to action at the page's section picker. It's only offered
     * on a section's own source, which the picker needs to leave that section
     * out of the choices.
     *
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>|null
     */
    private function moveToSectionAction(array $action): ?array
    {
        if (($action['key'] ?? null) !== MoveToSection::class) {
            return $action;
        }

        [$sourceKey] = $this->sourceState();

        if (! is_string($sourceKey) || ! str_starts_with($sourceKey, 'section:')) {
            return null;
        }

        unset($action['disabled']);

        return [
            ...$action,
            'selectionAttribute' => 'movable',
            'action' => [
                'type' => 'event',
                'name' => self::MOVE_TO_SECTION_EVENT,
                'detail' => [
                    'siteId' => $this->siteId(),
                    'currentSectionUid' => substr($sourceKey, strlen('section:')),
                ],
            ],
        ];
    }

    /**
     * The index page's URI. `$page` is already the slugged URL segment (the
     * route is `content/{page}`); the `entries` fallback mirrors
     * {@see Section::getCpIndexUri()} for the same reason — every entry index
     * lives under a page, named or not.
     */
    private function pageUri(): string
    {
        return sprintf('content/%s', $this->page ?: 'entries');
    }
}
