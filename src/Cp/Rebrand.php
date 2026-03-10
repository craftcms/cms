<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Storage;

class Rebrand
{
    /**
     * @var array[]|false[]
     */
    private array $_imageVariables = [];

    public function getImage(string $type)
    {
        if (! in_array($type, ['icon', 'logo'], true)) {
            return null;
        }

        /**
         * @TODO is this even worth it?
         *
         * I'm assuming it probably is, especially if the rebrand dist is remote.
         */
        if (! isset($this->_imageVariables[$type])) {
            $this->_imageVariables[$type] = collect(Storage::disk('rebrand')->files($type))
                ->filter(fn ($file) => ImageHelper::canManipulateAsImage(Str::after($file, '.')))
                ->map(fn ($file) => [
                    'name' => basename($file),
                    'path' => Storage::disk('rebrand')->path($file),
                    'url' => Storage::disk('rebrand')->url($file),
                ])
                ->first();
        }

        return $this->_imageVariables[$type];
    }
}
