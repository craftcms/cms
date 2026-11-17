<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Image\ImageHash;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Finds images that look like copies of each other, by their difference hashes.
 *
 * Each image’s hash is indexed in four 16-bit bands. Hashes that differ in up to three bits always share a band, and
 * ones that differ in a few more usually do, so images that share one are checked for how close their whole hashes
 * are. That keeps finding copies fast in large libraries.
 *
 * @see ImageHash
 * @since 6.0.0
 */
#[Singleton]
class ImageDuplicates
{
    /**
     * How many bits images’ hashes can differ in for them to count as copies. Resized, recompressed, and re-encoded
     * copies usually differ in up to four or so; cropped ones don’t count.
     */
    public const int MAX_DISTANCE = 5;

    private const int BANDS = 4;

    /**
     * Indexes an asset’s hash, or removes it from the index if it has none.
     */
    public function index(int $assetId, ?string $hash): void
    {
        DB::table(Table::IMAGEHASHES)->where('assetId', $assetId)->delete();

        if (! ImageHash::isHash($hash)) {
            return;
        }

        DB::table(Table::IMAGEHASHES)->insert(array_map(
            static fn (int $band, string $value): array => ['assetId' => $assetId, 'band' => $band, 'value' => $value],
            array_keys(self::bands($hash)),
            self::bands($hash),
        ));
    }

    /**
     * Returns the images that look like copies of an asset, closest first.
     *
     * @return list<Asset>
     */
    public function find(Asset $asset, int $limit = 5): array
    {
        $hash = $asset->analysis?->hash;

        return $hash !== null ? $this->findByHash($hash, $limit, $asset->id) : [];
    }

    /**
     * Returns the images that look like copies of the image a hash was found for, closest first.
     *
     * @param  int|null  $excludeId  An asset to leave out, such as the one the hash is for
     * @return list<Asset>
     */
    public function findByHash(string $hash, int $limit = 5, ?int $excludeId = null): array
    {
        if (! ImageHash::isHash($hash)) {
            return [];
        }

        $candidates = DB::table(Table::IMAGEHASHES, 'imagehashes')
            ->join(Table::ASSETS.' as assets', 'assets.id', '=', 'imagehashes.assetId')
            ->where(function (Builder $query) use ($hash): void {
                foreach (self::bands($hash) as $band => $value) {
                    $query->orWhere(fn (Builder $query) => $query->where('imagehashes.band', $band)->where('imagehashes.value', $value));
                }
            })
            ->when($excludeId !== null, fn ($query) => $query->where('imagehashes.assetId', '!=', $excludeId))
            // Keyed by asset ID, so assets that share several bands are only checked once
            ->pluck('assets.analysis', 'imagehashes.assetId');

        $distances = [];

        foreach ($candidates as $assetId => $analysis) {
            $candidateHash = Json::decode((string) $analysis)['hash'] ?? null;

            if (ImageHash::isHash($candidateHash) && ($distance = ImageHash::distance($hash, $candidateHash)) <= self::MAX_DISTANCE) {
                $distances[$assetId] = $distance;
            }
        }

        if ($distances === []) {
            return [];
        }

        asort($distances);
        $assets = Asset::find()
            ->id(array_keys($distances))
            ->status(null)
            ->indexBy('id')
            ->all();

        return array_slice(array_values(array_filter(array_map(
            static fn (int $assetId): ?Asset => $assets[$assetId] ?? null,
            array_keys($distances),
        ))), 0, $limit);
    }

    /**
     * @return array<int, string> The hash’s bands of four hexadecimal digits, by their position
     */
    private static function bands(string $hash): array
    {
        return str_split($hash, intdiv(16, self::BANDS));
    }
}
