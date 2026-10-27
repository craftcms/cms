<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Asset;

use BackedEnum;
use CraftCms\Cms\Asset\Enums\ImageType;
use CraftCms\Cms\Element\Queries\AssetQuery;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;

/**
 * @internal
 */
trait QueriesImageType
{
    /**
     * @var mixed The kind(s) of image that the resulting assets must be, as found by the “Classify images” AI task.
     *
     * @see ImageType
     *
     * @used-by imageType()
     */
    public mixed $imageType = null;

    protected function initQueriesImageType(): void
    {
        $this->beforeQuery(static function (AssetQuery $assetQuery) {
            static::applyImageType($assetQuery, $assetQuery->imageType);
        });
    }

    public static function applyImageType(BuilderContract $query, mixed $value): void
    {
        if ($value === null || $value === []) {
            return;
        }

        $query->whereParam('assets.imageType', is_array($value)
            ? array_map(static fn (mixed $item): mixed => $item instanceof BackedEnum ? $item->value : $item, $value)
            : ($value instanceof BackedEnum ? $value->value : $value));
    }

    /**
     * Narrows the query results based on what kind of image the assets are, as found by the “Classify images” AI task.
     *
     * Possible values include:
     *
     * | Value | Fetches images…
     * | - | -
     * | `'photo'` | that are photos.
     * | `['screenshot', 'diagram']` | that are screenshots or diagrams.
     * | `['not', 'logo']` | that aren’t logos.
     *
     * @param  string|ImageType|array<string|ImageType>|null  $value
     *
     * @uses $imageType
     */
    public function imageType(mixed $value): static
    {
        $this->imageType = $value;

        return $this;
    }
}
