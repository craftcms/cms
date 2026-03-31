<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Resources;

use CraftCms\Cms\Entry\Resources\EntryTypeResource;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Section */
class SectionResource extends JsonResource
{
    #[\Override]
    public static $wrap;

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
            'maxAuthors' => $this->maxAuthors ?? 1,
            'maxLevels' => $this->maxLevels,
            'propagationMethod' => $this->propagationMethod->value,
            'defaultPlacement' => $this->defaultPlacement->value,
            'previewTargets' => $this->previewTargets ?? [],
            'entryTypes' => EntryTypeResource::collection($this->entryTypes),
        ];
    }
}
