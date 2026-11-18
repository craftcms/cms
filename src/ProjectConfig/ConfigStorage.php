<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\DependencyAwareCache\Dependency\CallbackDependency;
use Exception;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * @internal
 *
 * @since 6.0.0
 */
class ConfigStorage
{
    /**
     * Top-level keys that nearly every request reads. They're stored in the cached index, so one cache read gets them.
     */
    private const array BOOT_ROOTS = [
        ProjectConfig::PATH_DATE_MODIFIED,
        'mcp',
        ProjectConfig::PATH_PLUGINS,
        ProjectConfig::PATH_ROUTES,
        ProjectConfig::PATH_SYSTEM,
        ProjectConfig::PATH_USERS,
    ];

    /**
     * @var array<string|int, mixed>|null The whole config this request read from the database. Later reads use it, so
     *                                    they don't read the database again when the cache doesn't hold it.
     */
    private ?array $stored = null;

    /**
     * @var array{version: string, roots: list<string>, boot: array<string, mixed>}|null The cached index this request
     *                                                                                   checked, with its version, top-level keys, and boot keys' values.
     */
    private ?array $index = null;

    /**
     * @var array<string, mixed> Top-level keys this request loaded from the cache, before anything rebuilt [[$stored]].
     */
    private array $roots = [];

    public function dependency(): CallbackDependency
    {
        return new CallbackDependency(static fn () => Info::fetch()->configVersion);
    }

    /**
     * Returns the stored value of a top-level key.
     *
     * The cache holds an index of the top-level keys, which includes the values of [[BOOT_ROOTS]], plus one entry per
     * other top-level key. Each is tagged with the `configVersion` it was built from.
     */
    public function readRoot(string $root, ?int $cacheDuration): mixed
    {
        // Once this request has read the database, every read uses that, so it doesn't mix in older cached keys
        if ($this->stored !== null) {
            return $this->stored[$root] ?? null;
        }

        if (array_key_exists($root, $this->roots)) {
            return $this->roots[$root];
        }

        if (! Cms::isInstalled()) {
            return null;
        }

        return $this->roots[$root] = $this->readCachedRoot($root, $cacheDuration);
    }

    /**
     * Returns the whole stored config.
     *
     * @return array<string|int, mixed>
     */
    public function readDatabase(?int $cacheDuration): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        if (! Cms::isInstalled()) {
            return [];
        }

        $version = Info::fetch()->configVersion;
        $index = $this->index ??= Cache::get(ProjectConfig::STORED_CACHE_KEY) ?: null;

        if (($index['version'] ?? null) !== $version) {
            return $this->rebuildCache($cacheDuration);
        }

        $roots = array_values(array_diff($index['roots'], array_keys($index['boot'])));
        $entries = $roots === [] ? [] : Cache::many(array_map($this->rootCacheKey(...), $roots));
        $data = [];

        foreach ($index['roots'] as $root) {
            if (array_key_exists((string) $root, $index['boot'])) {
                $data[$root] = $index['boot'][$root];

                continue;
            }

            $entry = $entries[$this->rootCacheKey($root)] ?? null;

            if (($entry['version'] ?? null) !== $version) {
                return $this->rebuildCache($cacheDuration, $root);
            }

            $data[$root] = $entry['value'];
        }

        return $data;
    }

    /**
     * Forgets the config this request loaded.
     */
    public function reset(): void
    {
        $this->stored = null;
        $this->index = null;
        $this->roots = [];
    }

    private function readCachedRoot(string $root, ?int $cacheDuration): mixed
    {
        $version = Info::fetch()->configVersion;
        $index = $this->index ??= Cache::get(ProjectConfig::STORED_CACHE_KEY) ?: null;

        if (($index['version'] ?? null) !== $version) {
            return $this->rebuildCache($cacheDuration)[$root] ?? null;
        }

        if (! in_array($root, $index['roots'], true)) {
            return null;
        }

        if (array_key_exists($root, $index['boot'])) {
            return $index['boot'][$root];
        }

        $entry = Cache::get($this->rootCacheKey($root));

        if (($entry['version'] ?? null) !== $version) {
            return $this->rebuildCache($cacheDuration, $root)[$root] ?? null;
        }

        return $entry['value'];
    }

    /**
     * Reads the whole config from the database, and writes it to the cache unless another request is already doing so.
     *
     * @param  string|null  $missingRoot  The top-level key whose entry was missing or stale, if the cached index was current.
     * @return array<string|int, mixed>
     */
    private function rebuildCache(?int $cacheDuration, ?string $missingRoot = null): array
    {
        // Another request may have changed the config since this one loaded its version, so tag the entries with the
        // stored version. It's read before the rows: if a change lands in between, the entries get the older version
        // and the next reader rebuilds them, rather than storing old rows under the new version.
        $version = DB::table(Table::INFO)->value('configVersion');
        $data = [];

        // Paths only need parent-before-child ordering, not locale-aware sorting.
        $orderBy = DB::connection()->isPgsql() ? new Expression('path COLLATE "C"') : 'path';

        foreach (DB::table(Table::PROJECTCONFIG)->orderBy($orderBy)->pluck('value', 'path') as $path => $value) {
            $value = Json::decode(Str::decdec($value));
            $segments = ProjectConfigHelper::pathSegments($path);
            $cursor = &$data;

            foreach ($segments as $segment) {
                // Older config data can contain a scalar at a path that also has descendants.
                if (! is_array($cursor)) {
                    $cursor = [];
                }

                $cursor[$segment] ??= [];
                $cursor = &$cursor[$segment];
            }

            $cursor = $value;
            unset($cursor);
        }

        $data = $this->stored = ProjectConfigHelper::cleanupConfig($data);

        // Every request that misses needs the data, but only one needs to write it. Requests that miss while it's
        // being written skip the write, and ones that get the lock afterward find the cache already written.
        $lock = Cache::lock(ProjectConfig::STORED_CACHE_KEY.':rebuild', 10);

        if (! $lock->get()) {
            return $data;
        }

        try {
            // Another request may have written the cache while this one read the database
            $written = (Cache::get(ProjectConfig::STORED_CACHE_KEY)['version'] ?? null) === $version
                && ($missingRoot === null || (Cache::get($this->rootCacheKey($missingRoot))['version'] ?? null) === $version);

            if (! $written) {
                $boot = array_intersect_key($data, array_flip(self::BOOT_ROOTS));
                $entries = [];

                foreach (array_diff_key($data, $boot) as $root => $value) {
                    $entries[$this->rootCacheKey((string) $root)] = ['version' => $version, 'value' => $value];
                }

                // The index goes last, so stores that write one entry at a time never point readers at missing entries
                $entries[ProjectConfig::STORED_CACHE_KEY] = [
                    'version' => $version,
                    'roots' => array_map(strval(...), array_keys($data)),
                    'boot' => $boot,
                ];
                Cache::putMany($entries, $cacheDuration);
            }
        } finally {
            $lock->release();
        }

        return $data;
    }

    private function rootCacheKey(string $root): string
    {
        return ProjectConfig::STORED_CACHE_KEY.':'.$root;
    }

    /** @param list<array{added?: array<string, mixed>, removed?: array<string, mixed>, message?: string}> $changes */
    public function save(array $changes): void
    {
        DB::transaction(function () use ($changes): void {
            foreach ($changes as $change) {
                $remove = array_keys($change['removed'] ?? []);
                $rows = [];

                foreach ($change['added'] ?? [] as $path => $value) {
                    $parent = $path;

                    // Delete parent keys, as they cannot hold a value AND be an array at the same time.
                    while (($parent = ProjectConfigHelper::pathWithoutLastSegment($parent)) !== null) {
                        $remove[] = $parent;
                    }

                    $encoded = ProjectConfigHelper::encodeValueAsString($value);

                    if (DB::isMysql() && preg_match('/[\x{10000}-\x{10FFFF}]/u', $encoded)) {
                        $encoded = 'base64:'.base64_encode($encoded);
                    }

                    $rows[] = ['path' => $path, 'value' => $encoded];
                }

                foreach (array_chunk(array_unique($remove), 500) as $paths) {
                    DB::table(Table::PROJECTCONFIG)->whereIn('path', $paths)->delete();
                }

                foreach (array_chunk($rows, 500) as $batch) {
                    DB::table(Table::PROJECTCONFIG)->upsert($batch, ['path'], ['value']);
                }
            }

            $this->invalidate();
        });
    }

    public function invalidate(): void
    {
        Info::fetch()->update(['configVersion' => Str::random(12)]);
        Cache::forget(ProjectConfig::STORED_CACHE_KEY);
        Cache::forget(ProjectConfig::DIFF_CACHE_KEY);
    }

    public function directory(string $folder): string
    {
        return Path::config($folder);
    }

    public function exists(string $folder): bool
    {
        return File::isFile($this->directory($folder).'/'.ProjectConfig::CONFIG_FILENAME);
    }

    /** @return array<string|int, mixed>|null */
    public function readYaml(string $folder): ?array
    {
        if (! $this->exists($folder)) {
            return null;
        }

        $data = [];
        $files = new Finder()
            ->files()
            ->in($this->directory($folder))
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->name('/\.yaml$/i')
            ->sortByName();

        foreach ($files as $file) {
            $value = Yaml::parseFile($file->getPathname()) ?? [];

            if (! is_array($value)) {
                throw new RuntimeException("Project config file {$file->getPathname()} must contain a mapping.");
            }

            $segments = $file->getRelativePath() === '' ? [] : explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
            $name = preg_replace('/\.yaml$/i', '', $file->getFilename());
            // In <handle>--<uid> filenames, only the UID belongs to the config path.
            $name = preg_replace('/^\w+--(?='.Str::uuidPattern().'$)/', '', $name);

            if ($segments !== [] && $name !== end($segments)) {
                $segments[] = $name;
            }

            $cursor = &$data;

            foreach ($segments as $segment) {
                $cursor[$segment] ??= [];
                $cursor = &$cursor[$segment];
            }

            $cursor = array_replace_recursive($cursor, $value);
            unset($cursor);
        }

        return $data;
    }

    /** @param array<string|int, mixed> $data */
    public function writeYaml(string $folder, array $data): void
    {
        $directory = $this->directory($folder);

        try {
            File::makeDirectory($directory);

            // Existing files get overwritten in place, and stale ones are only deleted once everything else
            // has been written, so a failed write doesn’t leave the folder empty (except hidden files/folders)
            $staleFiles = [];
            $existingFilesByLowerPath = [];

            foreach (Finder::create()->files()->in($directory) as $file) {
                $path = File::normalizePath($file->getPathname());
                $staleFiles[$path] = true;
                $existingFilesByLowerPath[strtolower($path)][] = $path;
            }

            $names = $data['meta']['__names__'] ?? [];

            foreach (ProjectConfigHelper::splitConfigIntoComponents($data) as $filename => $component) {
                $yaml = Yaml::dump(ProjectConfigHelper::cleanupConfig($component), 20, 2, Yaml::DUMP_COMPACT_NESTED_MAPPING);
                $yaml = preg_replace_callback('/^.*?('.Str::uuidPattern().').*$/m', function (array $line) use ($names): string {
                    $name = trim(str_replace(["\r", "\n"], ' ', $names[$line[1]] ?? ''));

                    return $name === '' ? $line[0] : $line[0].' # '.$name;
                }, $yaml);
                $path = File::normalizePath($directory.'/'.$filename);

                // Delete any existing file whose name only differs in case (e.g. a renamed handle),
                // so case-insensitive file systems pick up the new name
                foreach ($existingFilesByLowerPath[strtolower($path)] ?? [] as $existingFile) {
                    if ($existingFile !== $path) {
                        $this->deleteFile($existingFile);
                        unset($staleFiles[$existingFile]);
                    }
                }

                File::writeToFile($path, $yaml);
                unset($staleFiles[$path]);
            }

            foreach (array_keys($staleFiles) as $file) {
                $this->deleteFile($file);
            }

            // Remove folders left empty, deepest first, but not ones that still have hidden files/folders
            $directories = array_map(
                fn (SplFileInfo $dir): string => $dir->getPathname(),
                iterator_to_array(Finder::create()->directories()->in($directory), false),
            );
            rsort($directories);

            foreach ($directories as $dir) {
                if (count(scandir($dir) ?: []) === 2) {
                    File::deleteDirectory($dir);
                }
            }
        } catch (Throwable $exception) {
            // Don’t use the YAML files until they’ve been written successfully
            Cache::put(ProjectConfig::FILE_ISSUES_CACHE_KEY, true, ProjectConfig::CACHE_DURATION);

            throw new Exception("Unable to write new project config files: {$exception->getMessage()}", 0, $exception);
        }

        Cache::forget(ProjectConfig::FILE_ISSUES_CACHE_KEY);
        event(new YamlFilesWritten);
    }

    public function updateParsedTime(string $folder): bool
    {
        $file = $this->directory($folder).'/'.ProjectConfig::CONFIG_FILENAME;
        clearstatcache(true, $file);

        return Cache::put(ProjectConfig::CACHE_KEY, File::exists($file) ? File::lastModified($file) : 0, ProjectConfig::CACHE_DURATION);
    }

    public function parsedTimeMatches(string $folder): bool
    {
        $file = $this->directory($folder).'/'.ProjectConfig::CONFIG_FILENAME;
        clearstatcache(true, $file);

        return Cache::get(ProjectConfig::CACHE_KEY) === (File::exists($file) ? File::lastModified($file) : 0);
    }

    /** @param list<array{added?: array<string, mixed>, removed?: array<string, mixed>, message?: string}> $changes */
    public function writeDelta(array $changes, int $maxDeltas): void
    {
        $entries = [];

        foreach ($changes as $change) {
            $entry = array_filter($change, fn (mixed $value): bool => $value !== []);

            foreach (array_intersect_key($change['added'] ?? [], $change['removed'] ?? []) as $path => $value) {
                $old = $change['removed'][$path];
                $comparableOld = is_bool($old) ? (int) $old : $old;
                $comparableNew = is_bool($value) ? (int) $value : $value;

                if ($comparableOld !== $comparableNew) {
                    $entry['changed'][$path] = ['from' => $old, 'to' => $value];
                }

                unset($entry['added'][$path], $entry['removed'][$path]);
            }

            $entry = array_filter($entry, fn (mixed $value): bool => $value !== []);

            if ($entry !== []) {
                $entries[] = $entry;
            }
        }

        if ($entries === []) {
            return;
        }

        $path = Path::configDelta(ProjectConfig::CONFIG_DELTA_FILENAME);
        File::cycle($path, $maxDeltas);
        File::writeToFile($path, Yaml::dump(['dateApplied' => now()->format('Y-m-d H:i:s'), 'changes' => $entries], 20, 2));
    }

    private function deleteFile(string $file): void
    {
        if (! File::delete($file) && File::exists($file)) {
            throw new RuntimeException("Unable to delete \"$file\".");
        }
    }
}
