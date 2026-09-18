<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Env;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);
        /** @var array<string, array<string, mixed>>|null $filesystems */
        $filesystems = $projectConfig->get('fs');
        $filesystems = is_array($filesystems) ? $filesystems : [];
        $volumes = $projectConfig->get(ProjectConfig::PATH_VOLUMES);
        $volumes = is_array($volumes) ? $volumes : [];
        $transformers = $projectConfig->get(ProjectConfig::PATH_ASSET_TRANSFORMERS);
        $transformers = is_array($transformers) ? $transformers : [];
        $configuredDisks = config('filesystems.disks', []);
        $configuredDisks = is_array($configuredDisks) ? $configuredDisks : [];
        $missingDisks = [];

        foreach ($volumes as $volume) {
            if (! is_array($volume)) {
                continue;
            }

            $this->requireConfiguredDisk($volume['fs'] ?? null, $filesystems, $configuredDisks, $missingDisks);
        }

        foreach ($transformers as $transformer) {
            if (! is_array($transformer) || ($transformer['driver'] ?? null) !== 'craft') {
                continue;
            }

            $settings = is_array($transformer['settings'] ?? null) ? $transformer['settings'] : [];
            $this->requireConfiguredDisk($settings['filesystem'] ?? null, $filesystems, $configuredDisks, $missingDisks);
        }

        foreach (DB::table(Table::VOLUMES)->pluck('fs') as $reference) {
            $this->requireConfiguredDisk($reference, $filesystems, $configuredDisks, $missingDisks);
        }

        if ($missingDisks !== []) {
            $handles = implode(', ', array_map(fn (string $handle): string => "[$handle]", array_keys($missingDisks)));
            $suggestions = implode("\n\n", array_map(
                $this->suggestedDiskConfig(...),
                array_keys($missingDisks),
                $missingDisks,
            ));

            throw new RuntimeException(
                "The Craft Filesystem concept has been removed. You should instead configure Laravel filesystem disks named $handles ".
                "in config/filesystems.php with settings equivalent to the previous Craft Filesystem definitions, then run the upgrade again:\n\n$suggestions",
            );
        }

        if (! Schema::hasColumn(Table::VOLUMES, 'hasUrls')) {
            Schema::table(Table::VOLUMES, fn (Blueprint $table) => $table->boolean('hasUrls')->default(false)->after('fs'));
        }

        foreach ($volumes as $uid => $volume) {
            if (! is_array($volume)) {
                continue;
            }

            $reference = $volume['fs'] ?? null;
            $diskName = $this->diskName($reference);
            if ($diskName === null) {
                continue;
            }

            $volume['fs'] = $this->diskReference($reference, $diskName);
            $volume['hasUrls'] = $this->referenceHasUrls($reference, $filesystems, $configuredDisks);
            $volumes[$uid] = $volume;
        }

        foreach ($transformers as $uid => $transformer) {
            if (! is_array($transformer) || ($transformer['driver'] ?? null) !== 'craft') {
                continue;
            }

            $settings = is_array($transformer['settings'] ?? null) ? $transformer['settings'] : [];
            $reference = $settings['filesystem'] ?? null;
            $diskName = $this->diskName($reference);
            unset($settings['filesystem']);
            $settings['disk'] = $diskName === null ? null : $this->diskReference($reference, $diskName);
            $settings['hasUrls'] = $this->referenceHasUrls($reference, $filesystems, $configuredDisks);
            $transformer['settings'] = $settings;
            $transformers[$uid] = $transformer;
        }

        foreach (DB::table(Table::VOLUMES)->select(['id', 'fs'])->get() as $volume) {
            $diskName = $this->diskName($volume->fs);
            if ($diskName === null) {
                continue;
            }

            DB::table(Table::VOLUMES)
                ->where('id', $volume->id)
                ->update([
                    'fs' => $this->diskReference($volume->fs, $diskName),
                    'hasUrls' => $this->referenceHasUrls($volume->fs, $filesystems, $configuredDisks),
                ]);
        }

        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            $projectConfig->set(ProjectConfig::PATH_VOLUMES, $volumes, 'Convert volumes to Laravel filesystem disks');
            $projectConfig->set(ProjectConfig::PATH_ASSET_TRANSFORMERS, $transformers, 'Convert Asset Transformers to Laravel filesystem disks');
            $projectConfig->remove('fs', 'Remove Craft filesystem definitions');
            $projectConfig->saveModifiedConfigData();
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $filesystems
     * @param  array<string, mixed>  $configuredDisks
     * @param  array<string, array<string, mixed>>  $missingDisks
     */
    private function requireConfiguredDisk(mixed $reference, array $filesystems, array $configuredDisks, array &$missingDisks): void
    {
        $disk = $this->diskName($reference);
        if ($disk === null || ! isset($filesystems[$disk])) {
            return;
        }

        if (! array_key_exists($disk, $configuredDisks)) {
            $missingDisks[$disk] = $filesystems[$disk];
        }
    }

    /**
     * Renders a `config/filesystems.php` disk entry equivalent to a legacy Craft filesystem definition, for use in
     * the upgrade error message.
     *
     * @param  array<string, mixed>  $filesystem
     */
    private function suggestedDiskConfig(string $handle, array $filesystem): string
    {
        $type = $filesystem['type'] ?? null;
        $settings = is_array($filesystem['settings'] ?? null) ? $filesystem['settings'] : [];

        if ($type === 'craft\fs\Local' || $type === 'craft\fs\Temp' || $type === 'CraftCms\Cms\Filesystem\Filesystems\Local') {
            return $this->localDiskConfig($handle, $filesystem, $settings);
        }

        $typeLabel = is_string($type) && $type !== '' ? $type : 'unknown';

        return "'$handle' => [\n".
            "    // No automatic Laravel disk equivalent for Craft Filesystem type \"$typeLabel\". You will have to create this manually.\n".
            '    // Previous settings: '.json_encode($settings)."\n".
            '],';
    }

    /**
     * @param  array<string, mixed>  $filesystem
     * @param  array<string, mixed>  $settings
     */
    private function localDiskConfig(string $handle, array $filesystem, array $settings): string
    {
        $path = $settings['path'] ?? null;
        $root = is_string($path) && $path !== '' ? Env::parse($path) : null;

        $lines = [
            "'$handle' => [",
            "    'driver' => 'local',",
            $root !== null
                ? '    '.$this->phpArrayLine('root', $root)
                : "    'root' => null, // TODO: fill in the previous filesystem's base path",
        ];

        if ($this->hasUrls($filesystem)) {
            $url = $settings['url'] ?? null;
            $url = is_string($url) && $url !== '' ? rtrim((string) Env::parse($url), '/') : null;

            $lines[] = $url !== null
                ? '    '.$this->phpArrayLine('url', $url)
                : "    'url' => null, // TODO: fill in the previous filesystem's base URL";
        }

        $lines[] = '],';

        return implode("\n", $lines);
    }

    private function phpArrayLine(string $key, string $value): string
    {
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);

        return "'$key' => '$escaped',";
    }

    private function diskName(mixed $reference): ?string
    {
        if (! is_string($reference) || $reference === '') {
            return null;
        }

        $reference = Env::parse($reference);
        if (! is_string($reference) || $reference === '') {
            return null;
        }

        return $reference;
    }

    private function diskReference(mixed $reference, string $diskName): string
    {
        if (is_string($reference) && (str_contains($reference, '$') || str_starts_with($reference, '@'))) {
            return $reference;
        }

        return $diskName;
    }

    /**
     * @param  array<string, array<string, mixed>>  $filesystems
     * @param  array<string, mixed>  $configuredDisks
     */
    private function referenceHasUrls(mixed $reference, array $filesystems, array $configuredDisks): bool
    {
        $diskName = $this->diskName($reference);
        if ($diskName === null) {
            return false;
        }

        if (isset($filesystems[$diskName])) {
            return $this->hasUrls($filesystems[$diskName]);
        }

        $url = is_array($configuredDisks[$diskName] ?? null)
            ? ($configuredDisks[$diskName]['url'] ?? null)
            : null;

        return is_string($url) && $url !== '';
    }

    /** @param array<string, mixed> $filesystem */
    private function hasUrls(array $filesystem): bool
    {
        return ($filesystem['settings']['hasUrls'] ?? $filesystem['hasUrls'] ?? false) === true;
    }
};
