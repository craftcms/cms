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
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class ConfigStorage
{
    public function dependency(): CallbackDependency
    {
        return new CallbackDependency(static fn () => Info::fetch()->configVersion);
    }

    /** @return array<string|int, mixed> */
    public function readDatabase(?int $cacheDuration): array
    {
        if (! Cms::isInstalled() || version_compare(Info::fetch()->schemaVersion, '3.1.1', '<')) {
            return [];
        }

        if (version_compare(Info::fetch()->schemaVersion, '3.4.4', '<')) {
            $value = Info::fetch()->getAttribute('config');

            if (! $value) {
                return [];
            }

            $value = Str::decdec($value);

            try {
                return Json::decode($value) ?? [];
            } catch (InvalidArgumentException) {
                return unserialize($value, ['allowed_classes' => false]);
            }
        }

        return DependencyCache::remember(ProjectConfig::STORED_CACHE_KEY, $cacheDuration, function (): array {
            $data = [];

            foreach (DB::table(Table::PROJECTCONFIG)->orderBy('path')->pluck('value', 'path') as $path => $value) {
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

            return ProjectConfigHelper::cleanupConfig($data);
        }, $this->dependency());
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
        $files = new Finder()->files()->in($this->directory($folder))->ignoreDotFiles(false)->ignoreVCS(false)->name('/\.yaml$/i')->sortByName();

        foreach ($files as $file) {
            $value = Yaml::parseFile($file->getPathname()) ?? [];

            if (! is_array($value)) {
                throw new RuntimeException("Project config file {$file->getPathname()} must contain a mapping.");
            }

            $segments = $file->getRelativePath() === '' ? [] : explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
            $name = preg_replace('/\.yaml$/i', '', $file->getFilename());
            // In <handle>--<uid> filenames, only the UID belongs to the config path.
            $name = preg_replace('/^\w+--(?=[a-f0-9-]{36}$)/i', '', $name);

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
            $this->clearVisibleFiles($directory);
            $names = $data['meta']['__names__'] ?? [];

            foreach (ProjectConfigHelper::splitConfigIntoComponents($data) as $filename => $component) {
                $yaml = Yaml::dump(ProjectConfigHelper::cleanupConfig($component), 20, 2, Yaml::DUMP_COMPACT_NESTED_MAPPING);
                $yaml = preg_replace_callback('/^.*[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}.*$/mi', function (array $line) use ($names): string {
                    preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $line[0], $match);
                    $name = trim(str_replace(["\r", "\n"], ' ', $names[$match[0]] ?? ''));

                    return $name === '' ? $line[0] : $line[0].' # '.$name;
                }, $yaml);
                File::writeToFile($directory.'/'.$filename, $yaml);
            }
        } catch (Throwable $exception) {
            Cache::put(ProjectConfig::FILE_ISSUES_CACHE_KEY, true, ProjectConfig::CACHE_DURATION);

            // Remove any files already written so Craft cannot apply an incomplete config.
            try {
                $this->clearVisibleFiles($directory);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw new Exception('Unable to write new project config files', 0, $exception);
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

        if ($maxDeltas > 1) {
            File::delete($path.'.'.($maxDeltas - 1));
        }

        for ($index = $maxDeltas - 2; $index >= 0; $index--) {
            $source = $index === 0 ? $path : "$path.$index";

            if (File::exists($source)) {
                File::move($source, $path.'.'.($index + 1));
            }
        }

        File::writeToFile($path, Yaml::dump(['dateApplied' => now()->format('Y-m-d H:i:s'), 'changes' => $entries], 20, 2));
    }

    private function clearVisibleFiles(string $directory): void
    {
        // Delete everything except hidden files/folders.
        foreach (glob($directory.'/*') ?: [] as $path) {
            if (is_dir($path) && ! is_link($path)) {
                $deleted = File::deleteDirectory($path);
            } else {
                $deleted = File::delete($path);
            }

            if (! $deleted) {
                throw new RuntimeException("Unable to remove $path.");
            }
        }
    }
}
