<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;

/**
 * Reads and writes assets’ file contents stored in the `assetcontents` table.
 *
 * Text files’ contents are stored when they’re uploaded or indexed. Other files’ are stored when their assets are
 * soft-deleted, so the files can be put back if the assets are restored, after which those stored contents are
 * dropped.
 *
 * @since 6.0.0
 */
#[Singleton]
class AssetContents
{
    /**
     * The largest file, in bytes, whose contents are stored.
     */
    public const int MAX_SIZE = 16 * 1024 * 1024;

    /**
     * Returns whether a file of the given size is small enough to have its contents stored.
     */
    public function fits(?int $size): bool
    {
        return $size !== null && $size <= self::MAX_SIZE;
    }

    /**
     * Returns an asset’s stored contents, if it has any.
     */
    public function get(int $assetId): ?string
    {
        $query = DB::table(Table::ASSETCONTENTS)->where('assetId', $assetId);

        if (DB::isPgsql()) {
            $encoded = $query->selectRaw("encode(content, 'base64') as content")->first()?->content;

            return $encoded === null ? null : base64_decode((string) $encoded);
        }

        $content = $query->value('content');

        return is_resource($content) ? stream_get_contents($content) : $content;
    }

    /**
     * Returns whether an asset has stored contents.
     */
    public function has(int $assetId): bool
    {
        return DB::table(Table::ASSETCONTENTS)->where('assetId', $assetId)->exists();
    }

    /**
     * Stores an asset’s contents, or clears them when `$content` is `null`.
     */
    public function set(int $assetId, ?string $content): void
    {
        if ($content === null) {
            DB::table(Table::ASSETCONTENTS)->where('assetId', $assetId)->delete();

            return;
        }

        $now = Query::prepareDateForDb(now());

        DB::table(Table::ASSETCONTENTS)->upsert([
            'assetId' => $assetId,
            // Postgres won't take arbitrary bytes as a string parameter for a bytea column
            'content' => DB::isPgsql() ? DB::raw(sprintf("decode('%s', 'base64')", base64_encode($content))) : $content,
            'dateCreated' => $now,
            'dateUpdated' => $now,
        ], ['assetId'], ['content', 'dateUpdated']);
    }
}
