<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Resources;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;

use function CraftCms\Cms\currentUser;

/**
 * A section the current user can create entries in, with the entry types they can create in each of its sites, for
 * the control panel's New entry buttons.
 *
 * @mixin Section
 *
 * @since 6.0.0
 */
class SectionResource extends JsonResource
{
    /** @return array{uid: string, id: int, name: string, handle: string, type: string, sites: int[], entryTypes: list<array{uid: string, id: int, handle: string, name: string, icon: array<string, string>|null}>, entryTypeIdsBySite: object, canSave: bool|null} */
    #[\Override]
    public function toArray(Request $request): array
    {
        $entryTypesBySite = $this->availableEntryTypesBySite();
        $entryTypes = [];

        foreach ($entryTypesBySite as $siteEntryTypes) {
            foreach ($siteEntryTypes as $entryType) {
                $entryTypes[$entryType->id] ??= [
                    // For selection conditions’ Entry Type rules, which list entry types by UID
                    'uid' => $entryType->uid,
                    'id' => $entryType->id,
                    'handle' => $entryType->handle,
                    'name' => $entryType->getUiLabel(),
                    'icon' => $entryType->getIcon() ? Icons::resolveIconData($entryType->getIcon()) : null,
                ];
            }
        }

        return [
            'uid' => $this->uid,
            'id' => $this->id,
            // Translated, as the sidebar and the rest of the control panel show it
            'name' => $this->getUiLabel(),
            'handle' => $this->handle,
            'type' => $this->type->value ?? SectionType::Channel->value,
            'sites' => array_keys($entryTypesBySite),
            // Each entry type once, whichever sites it's available in, rather than once per site
            'entryTypes' => array_values($entryTypes),
            // An object, so resource filtering doesn't reindex the site IDs
            'entryTypeIdsBySite' => (object) array_map(
                fn (array $siteEntryTypes) => array_map(fn (EntryType $entryType) => $entryType->id, $siteEntryTypes),
                $entryTypesBySite,
            ),
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
