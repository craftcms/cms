<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Requests\ElementIndexScreenRequest;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

use function CraftCms\Cms\cp_redirect;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class EntriesIndexScreenController
{
    private ElementIndexScreenRequest $request;

    public function __construct(
        private readonly ElementIndexes $elementIndexes,
        private readonly ElementSources $elementSources,
    ) {}

    public function __invoke(
        ElementIndexScreenRequest $request,
        ?string $page = null,
        ?string $sectionHandle = null,
    ): Response|RedirectResponse {
        $this->request = $request;

        if (! $page || ! $this->elementSources->pageExists(Entry::class, $page)) {
            if ($sectionHandle !== null) {
                $redirect = Sections::getSectionByHandle($sectionHandle)?->getCpIndexUri();

                if ($redirect && $redirect !== $this->request->craftPath()) {
                    return cp_redirect($redirect);
                }
            }

            $firstPage = $this->elementSources->getFirstPage(Entry::class);

            if ($firstPage || $page !== 'entries') {
                return cp_redirect('content/'.Str::slug($firstPage ?? 'entries'));
            }
        }

        $sourceKey = $this->request->source() ?? $this->sourceKeyForSection($sectionHandle);

        $selectedSiteId = $this->selectedSiteId() ?? Sites::getCurrentSite()->id;

        $data = $this->elementIndexes->indexData(
            elementType: Entry::class,
            page: $page,
            sourceKey: $sourceKey,
            search: $this->request->search(),
            sortAttribute: $this->request->sortAttribute(),
            sortDirection: $this->request->sortDirection(),
            siteId: $selectedSiteId,
            status: $this->request->status(),
            pageNum: $this->request->page(),
            perPage: $this->request->limit(),
        );

        if ($data === null && $sourceKey !== null && $page) {
            return cp_redirect("content/$page");
        }

        $data ??= [
            'sources' => [],
            'selectedSource' => null,
            'columns' => [],
            'sortOptions' => [],
            'sort' => [],
            'elements' => [],
            'pagination' => null,
        ];

        return Inertia::render('entries/Index', [
            'title' => $this->title($page),
            'page' => $page,
            'sources' => $data['sources'],
            'selectedSource' => $data['selectedSource'],
            'columns' => $data['columns'],
            'sortOptions' => $data['sortOptions'],
            'sort' => $data['sort'],
            'elements' => fn () => $data['elements'],
            'pagination' => fn () => $data['pagination'],
            'searchTerm' => $this->request->search(),
            'sites' => Sites::getEditableSites()
                ->map(fn ($site) => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'handle' => $site->handle,
                ])
                ->values()
                ->all(),
            'selectedSiteId' => $selectedSiteId,
            'statuses' => collect(Entry::statuses())
                ->map(fn ($label, $value) => [
                    'value' => $value,
                    'label' => is_array($label) ? ($label['label'] ?? $value) : $label,
                ])
                ->values()
                ->all(),
            'selectedStatus' => $this->request->status(),
            ...$this->createButtonProps($data['selectedSource']),
        ]);
    }

    private function sourceKeyForSection(?string $sectionHandle): ?string
    {
        if ($sectionHandle === null) {
            return null;
        }

        if ($sectionHandle === 'singles') {
            return 'singles';
        }

        $section = Sections::getSectionByHandle($sectionHandle);

        return $section ? "section:$section->uid" : null;
    }

    private function selectedSiteId(): ?int
    {
        $siteId = $this->request->siteId();

        if ($siteId === null) {
            return null;
        }

        return Sites::getEditableSites()->contains(fn ($site) => $site->id === $siteId)
            ? $siteId
            : null;
    }

    private function title(?string $page): string
    {
        if ($page) {
            $pageName = $this->elementSources->getPages(Entry::class)
                ->first(fn (string $name) => Str::slug($name) === $page);

            if ($pageName) {
                return t($pageName, category: 'site');
            }
        }

        return t('Entries');
    }

    private function createButtonProps(?string $sourceKey): array
    {
        $props = ['canCreate' => false, 'newEntryUrl' => null];

        if (! $sourceKey || ! str_starts_with($sourceKey, 'section:')) {
            return $props;
        }

        $section = Sections::getSectionByUid(substr($sourceKey, strlen('section:')));

        if (
            $section === null ||
            $section->type === SectionType::Single ||
            ! currentUser()?->can("createEntries:$section->uid")
        ) {
            return $props;
        }

        return [
            'canCreate' => true,
            'newEntryUrl' => Url::cpUrl("entries/$section->handle/new"),
        ];
    }
}
