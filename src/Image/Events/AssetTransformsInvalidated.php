<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after the `craft` transformer deletes an asset’s generated transforms, once the change is committed.
 *
 * It fires once per asset, and only when the asset had generated transform files. Caches can use {@see urls()} to
 * purge the URLs the deleted transforms were served from.
 *
 * The files are deleted before the change is committed. If it’s rolled back after that, this isn’t dispatched, even
 * though the files are already gone.
 *
 * @since 6.0.0
 */
class AssetTransformsInvalidated implements ShouldDispatchAfterCommit
{
    public function __construct(
        public Asset $asset,
        /** @var list<array{disk: string, path: string, url: string|null}> The deleted transform files, with the URLs they were served from when the transforms have public URLs */
        public array $files,
    ) {}

    /**
     * Returns the URLs the deleted transforms were served from, without revision query parameters.
     *
     * @return list<string>
     */
    public function urls(): array
    {
        return array_values(array_filter(array_column($this->files, 'url')));
    }
}
