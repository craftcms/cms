<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Resources;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Shared\Enums\Color;
use Illuminate\Http\Request;

/**
 * @mixin EntryType
 *
 * @since 6.0.0
 */
class EntryTypeResource extends JsonResource
{
    /** @return array{name:string|null, id:int|null, handle:string|null, description:string|null, color:Color|null, indicators:array<array-key,mixed>, actions:array<array-key,mixed>, icon:array{name:string,family:string}|null, original:self} */
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
