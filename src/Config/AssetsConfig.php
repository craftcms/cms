<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\PHP;
use InvalidArgumentException;

/**
 * Settings for assets: which files can be uploaded, how they’re uploaded and named, where Craft keeps their files, and
 * which asset transformers volumes can use.
 *
 * @since 6.0.0
 */
class AssetsConfig extends BaseConfig
{
    /** @var list<string> The file extensions that can be uploaded, in lowercase. */
    public array $allowedFileExtensions = [
        '7z',
        'aiff',
        'asc',
        'asf',
        'avi',
        'avif',
        'bmp',
        'cap',
        'cin',
        'csv',
        'dfxp',
        'doc',
        'docx',
        'dotm',
        'dotx',
        'fla',
        'flv',
        'gif',
        'gz',
        'gzip',
        'heic',
        'heif',
        'hevc',
        'itt',
        'jp2',
        'jpeg',
        'jpg',
        'jpx',
        'js',
        'json',
        'jxl',
        'lrc',
        'm2t',
        'm4a',
        'm4v',
        'markdown',
        'mcc',
        'md',
        'mid',
        'mov',
        'mp3',
        'mp4',
        'mpc',
        'mpeg',
        'mpg',
        'mpsub',
        'ods',
        'odt',
        'ogg',
        'ogv',
        'pdf',
        'png',
        'potx',
        'pps',
        'ppsm',
        'ppsx',
        'ppt',
        'pptm',
        'pptx',
        'ppz',
        'pxd',
        'qt',
        'ram',
        'rar',
        'rm',
        'rmi',
        'rmvb',
        'rt',
        'rtf',
        'sami',
        'sbv',
        'scc',
        'sdc',
        'sitd',
        'smi',
        'srt',
        'stl',
        'sub',
        'svg',
        'swf',
        'sxc',
        'sxw',
        'tar',
        'tds',
        'tgz',
        'tif',
        'tiff',
        'ttml',
        'txt',
        'vob',
        'vsd',
        'vtt',
        'wav',
        'webm',
        'webp',
        'wma',
        'wmv',
        'xls',
        'xlsx',
        'zip',
    ];

    /**
     * @var list<string>|null File extensions that are merged into `allowedFileExtensions` as they’re set, so this is
     *                        always `null`.
     */
    public ?array $extraAllowedFileExtensions = null;

    /**
     * @var array<string, array<string, mixed>> Additional file kinds, merged with the ones
     *                                          [[\CraftCms\Cms\Asset\AssetFileKinds::fileKinds()]] defines.
     *
     * Their extensions still need to be listed in `allowedFileExtensions` or `extraAllowedFileExtensions` to be uploaded.
     */
    public array $extraFileKinds = [];

    /**
     * Whether uploaded filenames with non-ASCII characters should be converted to ASCII (`ñ` → `n`).
     *
     * `php artisan craft:utils:ascii-filenames` converts existing assets’ filenames.
     */
    public bool $convertFilenamesToAscii = false;

    /** The string that separates words in uploaded filenames, or `false` to leave spaces alone. */
    public string|false $filenameWordSeparator = '-';

    /** Whether asset URLs should be revved, so browsers don’t load cached versions when they’re modified. */
    public bool $revUrls = false;

    /** Whether uploaded SVG files should be sanitized. Enable it if untrusted users can upload SVGs. */
    public bool $sanitizeSvgUploads = true;

    /** Whether images uploaded through the control panel should be sanitized. */
    public bool $sanitizeCpImageUploads = true;

    /**
     * The Laravel filesystem disk that soft-deleted assets’ files are moved to, so the assets can be restored.
     *
     * Files are stored at the root of the disk, named after their asset’s ID and extension, such as `123.jpg`. Each
     * environment should have its own disk, or its own folder on a shared one, which a `scoped` disk can provide.
     * If this isn’t set, soft-deleting an asset deletes its file, and the asset can’t be restored.
     */
    public ?string $trashDisk = null;

    /** The Laravel filesystem disk for temporary assets and user photos. A local temp folder is used by default. */
    public ?string $tempUploadDisk = null;

    /**
     * The Laravel filesystem disk that stages upload sessions.
     *
     * If this isn’t set, upload sessions are staged on the local `craft-tmp` disk. Sites that run on more than one
     * server must set it to a disk that every server shares, so each part of an upload can reach any of them.
     */
    public ?string $uploadSessionDisk = null;

    /** The registered uploader to use, or `null` to select one from the upload session disk. */
    public ?string $uploader = null;

    /**
     * The maximum bytes per PHP upload request. Proxy request limits may require a smaller value. S3 multipart uploads
     * use parts of at least 5 MiB.
     */
    public int $uploadChunkSize = 8 * 1024 * 1024;

    /** The seconds of inactivity before an upload session and its temporary files can be removed. */
    public int $uploadSessionDuration = 24 * 60 * 60;

    /**
     * The maximum upload file size, in bytes. It can be set to any value [[PHP::sizeToBytes()]] supports, such as `'25M'`.
     */
    public string|int $maxUploadFileSize = 16777216;

    /**
     * The transformer for volumes that don’t choose one. `null` uses `craft`, and lets a hosting package such as Craft
     * Cloud’s set its own default without overriding a project that chose one.
     */
    public ?string $defaultTransformer = null;

    /**
     * Transformers keyed by name. Each has a `driver`, registered with `AssetTransformers::extend()`, and that driver’s
     * settings. The `craft` transformer is always defined, and its entry here only holds its settings.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $transformers = [];

    /** @param list<string> $value */
    public function allowedFileExtensions(array $value): self
    {
        $this->allowedFileExtensions = array_map(strtolower(...), array_values($value));

        return $this;
    }

    /**
     * Adds file extensions to `allowedFileExtensions`.
     *
     * @param  list<string>|null  $value
     */
    public function extraAllowedFileExtensions(?array $value): self
    {
        if (is_array($value)) {
            $this->allowedFileExtensions = array_merge($this->allowedFileExtensions, array_map(strtolower(...), array_values($value)));
        }

        $this->extraAllowedFileExtensions = null;

        return $this;
    }

    /** @param array<string, array<string, mixed>> $value */
    public function extraFileKinds(array $value): self
    {
        $this->extraFileKinds = $value;

        return $this;
    }

    public function convertFilenamesToAscii(bool $value = true): self
    {
        $this->convertFilenamesToAscii = $value;

        return $this;
    }

    public function filenameWordSeparator(string|false $value): self
    {
        $this->filenameWordSeparator = $value;

        return $this;
    }

    public function revUrls(bool $value = true): self
    {
        $this->revUrls = $value;

        return $this;
    }

    public function sanitizeSvgUploads(bool $value = true): self
    {
        $this->sanitizeSvgUploads = $value;

        return $this;
    }

    public function sanitizeCpImageUploads(bool $value = true): self
    {
        $this->sanitizeCpImageUploads = $value;

        return $this;
    }

    public function trashDisk(?string $value): self
    {
        $this->trashDisk = $value;

        return $this;
    }

    public function tempUploadDisk(?string $value): self
    {
        $this->tempUploadDisk = $value;

        return $this;
    }

    public function uploadSessionDisk(?string $value): self
    {
        $this->uploadSessionDisk = $value;

        return $this;
    }

    public function uploader(?string $value): self
    {
        $this->uploader = $value;

        return $this;
    }

    public function uploadChunkSize(int $value): self
    {
        $this->uploadChunkSize = $value;

        return $this;
    }

    public function uploadSessionDuration(int $value): self
    {
        $this->uploadSessionDuration = $value;

        return $this;
    }

    public function maxUploadFileSize(string|int $value): self
    {
        $this->maxUploadFileSize = PHP::sizeToBytes($value);

        return $this;
    }

    public function defaultTransformer(?string $value): self
    {
        $this->defaultTransformer = $value !== '' ? $value : null;

        return $this;
    }

    /**
     * Defines a transformer. Its config needs a `driver`, and can hold that driver’s settings. Transformers with the
     * `craft` driver take `outputDisk` and `generateTransformsBeforePageLoad`. The `craft` transformer’s config can
     * leave out `driver`. Adding a name that’s already defined replaces its config.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidArgumentException if the name is empty, an integer, which PHP turns into an integer array key, or
     *                                  starts with `disk:`, which names disks’ deprecated `asset_transformer` keys, or
     *                                  if the `craft` transformer is given another driver.
     */
    public function addTransformer(string $name, array $config): self
    {
        // PHP turns integer-like array keys into integers, and `0` would resolve to the default transformer
        if ($name === '' || (string) (int) $name === $name) {
            throw new InvalidArgumentException('Asset transformer names can’t be empty or integers.');
        }

        if (str_starts_with($name, 'disk:')) {
            throw new InvalidArgumentException('Asset transformer names can’t start with “disk:”, which is reserved for disks’ deprecated `asset_transformer` keys.');
        }

        if ($name === 'craft' && ($config['driver'] ?? 'craft') !== 'craft') {
            throw new InvalidArgumentException('The craft transformer’s driver can’t be changed. Give the transformer another name.');
        }

        $this->transformers[$name] = $config;

        return $this;
    }

    /** @param array<string, array<string, mixed>> $value */
    public function transformers(array $value): self
    {
        $this->transformers = [];

        foreach ($value as $name => $config) {
            $this->addTransformer((string) $name, $config);
        }

        return $this;
    }

    /**
     * Returns a transformer’s config, or `null` if it isn’t defined. The `craft` transformer is always defined.
     *
     * @return array<string, mixed>|null
     */
    public function transformerConfig(string $name): ?array
    {
        if ($name === 'craft') {
            // The driver goes last, so a `null` driver in the config can't replace it
            return [...($this->transformers['craft'] ?? []), 'driver' => 'craft'];
        }

        return $this->transformers[$name] ?? null;
    }

    /**
     * Returns the names of the defined transformers, including `craft`.
     *
     * @return list<string>
     */
    public function transformerNames(): array
    {
        return array_values(array_unique(['craft', ...array_map(strval(...), array_keys($this->transformers))]));
    }

    /**
     * Returns the name of the disk for temporary assets and user photos.
     */
    public function getTempUploadDisk(): string
    {
        return $this->diskName($this->tempUploadDisk) ?? 'craft-asset-temp';
    }

    /**
     * Returns the name of the disk that stages upload sessions.
     */
    public function getUploadSessionDisk(): string
    {
        return $this->diskName($this->uploadSessionDisk) ?? 'craft-tmp';
    }

    /**
     * Returns the name of the disk that soft-deleted assets’ files are moved to, if one is set.
     */
    public function getTrashDisk(): ?string
    {
        return $this->diskName($this->trashDisk);
    }

    private function diskName(?string $value): ?string
    {
        $disk = Env::parse($value);

        return is_string($disk) && $disk !== '' ? $disk : null;
    }
}
