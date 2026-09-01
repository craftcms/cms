<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Resources;

use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Entry\Resources\EntryTypeResource;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use function CraftCms\Cms\currentUser;

/** @mixin Section */
class SectionResource extends JsonResource
{
    /** @return array<string, array<int, int|array<string, string>>|bool|int|string|null|AnonymousResourceCollection> */
    #[\Override]
    public function toArray(Request $request): array
    {
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
            'sites' => $this->getSiteIds(),
            'entryTypes' => EntryTypeResource::collection($this->entryTypes),
            'canSave' => currentUser()?->can("saveEntries:$this->uid"),
        ];
    }
}
