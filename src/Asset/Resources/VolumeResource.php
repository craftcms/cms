<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Resources;

use CraftCms\Cms\Asset\Data\Volume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Volume */
class VolumeResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'id' => $this->id,
            'name' => $this->name,
            'handle' => $this->handle,
            'fsHandle' => $this->getFsHandle(false),
            'subpath' => $this->getSubpath(false, false),
            'transformFsHandle' => $this->getTransformFsHandle(false),
            'transformSubpath' => $this->getTransformSubpath(false, false),
            'titleTranslationMethod' => $this->titleTranslationMethod->value,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat,
            'altTranslationMethod' => $this->altTranslationMethod->value,
            'altTranslationKeyFormat' => $this->altTranslationKeyFormat,
            'fieldLayout' => $this->getFieldLayout(),
        ];
    }
}
