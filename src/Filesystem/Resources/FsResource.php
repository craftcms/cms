<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Resources;

use CraftCms\Cms\Cp\JsonResource;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem;
use CraftCms\Cms\Filesystem\Filesystems\MissingFs;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;

/**
 * @mixin Filesystem
 */
class FsResource extends JsonResource
{
    /** @return array<string,mixed> */
    #[\Override]
    public function toArray(Request $request): array
    {
        $missing = $this->resource instanceof MissingFs;

        return Arr::merge(parent::toArray($request), [
            'missing' => $missing,
            'type' => $missing ? $this->resource->expectedType : $this::displayName(),
        ]);
    }
}
