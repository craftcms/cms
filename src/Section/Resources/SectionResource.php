<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Resources;

use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Resources\EntryTypeResource;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use function CraftCms\Cms\currentUser;

/**
 * @mixin Section
 *
 * @since 6.0.0
 */
class SectionResource extends JsonResource
{
    /** @return array<string, array<int, int|array<string, string>>|bool|int|string|null|object|AnonymousResourceCollection> */
    #[\Override]
    public function toArray(Request $request): array
    {
        $entryTypesBySite = $this->availableEntryTypesBySite();
        $requestedSiteId = app(RequestedSite::class)->get()?->id;

        return [
            'uid' => $this->uid,
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'type' => $this->type->value ?? SectionType::Channel->value,
            'enableVersioning' => $this->enableVersioning,
            'minAuthors' => $this->minAuthors,
            'maxAuthors' => $this->maxAuthors,
            'maxLevels' => $this->maxLevels,
            'propagationMethod' => $this->propagationMethod->value,
            'defaultPlacement' => $this->defaultPlacement->value,
            'previewTargets' => $this->previewTargets ?? [],
            'sites' => array_keys($entryTypesBySite),
            'entryTypes' => EntryTypeResource::collection($entryTypesBySite[$requestedSiteId] ?? Arr::first($entryTypesBySite) ?? []),
            // An object, so resource filtering doesn't reindex the site IDs
            'entryTypesBySite' => (object) array_map(fn (array $entryTypes) => EntryTypeResource::collection($entryTypes)->resolve($request), $entryTypesBySite),
            'canSave' => currentUser()?->can("saveEntries:$this->uid"),
        ];
    }

    /**
     * Returns the entry types the current user can create in each of the section’s sites, the same way
     * `CreateEntryController` determines them, omitting sites without any.
     *
     * @return array<int, EntryType[]>
     */
    private function availableEntryTypesBySite(): array
    {
        $userId = currentUser()?->getCraftUserId();

        return array_filter(Arr::mapWithKeys($this->getSiteIds(), function (int $siteId) use ($userId): array {
            $entry = new Entry;
            $entry->siteId = $siteId;
            $entry->sectionId = $this->id;

            if ($this->maxAuthors !== 0 && $userId !== null) {
                $entry->setAuthorIds([$userId]);
            }

            return [$siteId => $entry->getAvailableEntryTypes()];
        }));
    }
}
