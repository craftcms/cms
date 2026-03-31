<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryTypeResource extends JsonResource
{
    #[\Override]
    public static $wrap;

    #[\Override]
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
