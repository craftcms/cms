<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

/**
 * Reads Craft’s own keys on disks in `config/filesystems.php`, such as `asset_transformer` and `rev_urls`.
 *
 * @since 6.0.0
 */
class DiskConfig
{
    private const int MAX_SCOPED_DEPTH = 8;

    /**
     * Returns a key’s value on a disk, or `null` if it isn’t set. A `scoped` disk without the key follows the disk it
     * wraps.
     *
     * Disks are read from the `disks` array rather than with dot notation, so disk names can contain dots.
     *
     * @param  string|array<string, mixed>  $disk  A disk name, or a scoped disk’s inline `disk` config.
     */
    public static function get(string|array $disk, string $key): mixed
    {
        return self::find($disk, $key, 0);
    }

    /**
     * @param  string|array<string, mixed>  $disk
     */
    private static function find(string|array $disk, string $key, int $depth): mixed
    {
        $config = is_string($disk) ? (config('filesystems.disks')[$disk] ?? null) : $disk;

        if (! is_array($config) || $depth > self::MAX_SCOPED_DEPTH) {
            return null;
        }

        if (array_key_exists($key, $config)) {
            return $config[$key];
        }

        $wrapped = $config['disk'] ?? null;

        return ($config['driver'] ?? null) === 'scoped' && (is_string($wrapped) || is_array($wrapped))
            ? self::find($wrapped, $key, $depth + 1)
            : null;
    }
}
