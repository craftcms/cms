<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Resources;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Entry\Data\EntryType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EntryType */
class EntryTypeResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $elementHtml = app(ElementHtml::class);

        return parent::toArray($request) + [
            'chipHtml' => $elementHtml->chipHtml($this->resource, $request->chipConfig),
        ];
    }
}
