<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Resources;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Entry\Data\EntryType;
use Illuminate\Http\Request;

/** @mixin EntryType */
class EntryTypeResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'id' => $this->id,
            'handle' => $this->handle,
            'description' => $this->description,
            'color' => $this->getColor(),
            'indicators' => $this->getIndicators(),
            'actions' => $this->getActionMenuItems(),
            'icon' => $this->getIcon() ? Icons::resolveIconData($this->getIcon()) : null,
            'original' => EntryTypeResource::make($this->original),
        ];
    }
}
