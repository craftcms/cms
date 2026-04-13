<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Resources;

use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FsResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        $missing = $this->resource instanceof MissingFs;

        return Arr::merge(parent::toArray($request), [
            'missing' => $missing,
            'type' => $missing ? $this->expectedType : $this::displayName(),
        ]);
    }
}
