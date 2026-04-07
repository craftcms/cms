<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Resources;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntryType */
class EntryTypeResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $elementHtml = app(ElementHtml::class);

        return Arr::merge(parent::toArray($request), [
            'indicators' => $this->getIndicators(),
            'actions' => $this->getActionMenuItems(),
            'icon' => $this->getIcon() ? Icons::resolveIconData($this->getIcon()) : null,
            'chipHtml' => $elementHtml->chipHtml($this->resource, [
                'showHandle' => true,
                // 'checkbox' => $this->resource->selectable,
                'showActionMenu' => true,
                'showIndicators' => true,
                'showDescription' => true,
            ]),
        ]);
    }
}
