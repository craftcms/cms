<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Str;
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
            $suggestions = Str::indent($suggestions, '        ');

            throw new RuntimeException(<<<MESSAGE
                The Craft Filesystem concept has been removed.

                Configure Laravel filesystem disks named $handles in config/filesystems.php with settings equivalent to the previous Craft Filesystem definitions.

                If config/filesystems.php does not exist, publish it first:

                    php artisan config:publish filesystems --no-interaction

                Add these entries to its `disks` array:

                $suggestions

                Then run the upgrade again.
                MESSAGE);
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
            $projectConfig->set(ProjectConfig::PATH_ASSET_TRANSFORMERS, $transformers, 'Convert asset transformers to Laravel filesystem disks');
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

        $diskConfig = $configuredDisks[$disk] ?? null;
        if (! array_key_exists($disk, $configuredDisks) || (is_array($diskConfig) && ($diskConfig['_craft'] ?? false) === true)) {
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

        $credentials = [];
        $config = match ($type) {
            'craft\awss3\Fs' => [
                'driver' => "'s3'",
                'key' => $this->credentialExpression($handle, 'KEY_ID', $settings['keyId'] ?? null, $credentials),
                'secret' => $this->credentialExpression($handle, 'SECRET', $settings['secret'] ?? null, $credentials),
                'region' => $this->configExpression($settings['region'] ?? null),
                'bucket' => $this->configExpression($settings['bucket'] ?? null),
                'root' => $this->configExpression($settings['subfolder'] ?? null),
                'url' => $this->urlExpression($filesystem, $settings),
                'visibility' => ($settings['makeUploadsPublic'] ?? true) ? "'public'" : "'private'",
            ],
            'vaersaagod\dospaces\Fs', 'mwikala\linodes3\Fs' => [
                'driver' => "'s3'",
                'key' => $this->credentialExpression($handle, 'KEY_ID', $settings['keyId'] ?? null, $credentials),
                'secret' => $this->credentialExpression($handle, 'SECRET', $settings['secret'] ?? null, $credentials),
                'region' => $this->configExpression($settings['region'] ?? null),
                'bucket' => $this->configExpression($settings['bucket'] ?? null),
                'endpoint' => $this->configExpression($settings['endpoint'] ?? null),
                'root' => $this->configExpression($settings['subfolder'] ?? null),
                'url' => $this->urlExpression($filesystem, $settings),
            ],
            'jrrdnx\cloudflarer2\Fs' => [
                'driver' => "'s3'",
                'key' => $this->credentialExpression($handle, 'KEY_ID', $settings['keyId'] ?? null, $credentials),
                'secret' => $this->credentialExpression($handle, 'SECRET', $settings['secret'] ?? null, $credentials),
                'region' => $this->configExpression($settings['region'] ?? 'auto'),
                'bucket' => $this->configExpression(($settings['bucketSelectionMode'] ?? null) === 'manual' ? ($settings['manualBucket'] ?? null) : ($settings['bucket'] ?? null)),
                'endpoint' => ($accountId = $this->configExpression($settings['accountId'] ?? null)) !== null
                    ? "sprintf('https://%s.r2.cloudflarestorage.com', $accountId)"
                    : null,
                'root' => $this->configExpression($settings['subfolder'] ?? null),
                'url' => $this->urlExpression($filesystem, $settings),
            ],
            'craft\googlecloud\Fs' => [
                '// Install with: composer require spatie/laravel-google-cloud-storage:^2.4.2',
                'driver' => "'gcs'",
                'project_id' => $this->configExpression($settings['projectId'] ?? null),
                'key_file' => ($keyFile = $this->credentialExpression($handle, 'KEY_FILE', $settings['keyFileContents'] ?? null, $credentials)) !== null
                    ? "json_decode((string) $keyFile, true)"
                    : null,
                'bucket' => $this->configExpression($settings['bucket'] ?? null),
                'path_prefix' => $this->configExpression($settings['subfolder'] ?? null),
                'storage_api_uri' => $this->urlExpression($filesystem, $settings),
                'visibility' => $this->configExpression($settings['visibility'] ?? null) ?? ($this->hasUrls($filesystem) ? "'public'" : "'private'"),
            ],
            'craft\azureblob\Fs' => [
                '// Install with: composer require azure-oss/storage-blob-laravel',
                'driver' => "'azure-storage-blob'",
                'connection_string' => $this->credentialExpression($handle, 'CONNECTION_STRING', $settings['connectionString'] ?? null, $credentials),
                'container' => $this->configExpression($settings['container'] ?? null),
                'prefix' => $this->configExpression($settings['subfolder'] ?? null),
                'url' => $this->urlExpression($filesystem, $settings),
            ],
            default => null,
        };

        if ($config !== null) {
            $lines = ["'$handle' => ["];

            foreach ($credentials as $name) {
                $lines[] = "    // Set $name in .env to the previous filesystem's value.";
            }

            foreach (array_filter($config, fn (?string $expression) => $expression !== null) as $key => $expression) {
                $lines[] = is_int($key) ? "    $expression" : "    '$key' => $expression,";
            }

            $lines[] = '],';

            return implode("\n", $lines);
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
            $url = $settings['url'] ?? $filesystem['url'] ?? null;
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

    /**
     * Renders a setting as a PHP expression: `env()` for a `$ENV_VAR` reference, otherwise the parsed literal.
     */
    private function configExpression(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\$(\w+)$/', $value, $match)) {
            return "env('$match[1]')";
        }

        $parsed = (string) Env::parse($value);

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $parsed)."'";
    }

    /**
     * Renders a credential as an `env()` call, so the error message never contains its value.
     *
     * @param  string[]  $credentials  Environment variable names the user must set
     */
    private function credentialExpression(string $handle, string $suffix, mixed $value, array &$credentials): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\$(\w+)$/', $value, $match)) {
            return "env('$match[1]')";
        }

        $name = Str::of($handle)->snake()->upper()->append("_$suffix")->toString();
        $credentials[] = $name;

        return "env('$name')";
    }

    /**
     * @param  array<string, mixed>  $filesystem
     * @param  array<string, mixed>  $settings
     */
    private function urlExpression(array $filesystem, array $settings): ?string
    {
        if (! $this->hasUrls($filesystem)) {
            return null;
        }

        $url = $settings['url'] ?? $filesystem['url'] ?? null;

        return $this->configExpression(is_string($url) ? rtrim($url, '/') : null);
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
