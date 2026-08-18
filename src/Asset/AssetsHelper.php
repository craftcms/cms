<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\SetAssetFilename;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem as LaravelFilesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;
use Twig\Error\RuntimeError;

use function CraftCms\Cms\renderObjectTemplate;

class AssetsHelper
{
    public const string INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

    /**
     * Get a temporary file path.
     *
     * @param  string  $extension  extension to use. "tmp" by default.
     * @return string The temporary file path
     *
     * @throws Exception in case of failure
     */
    public static function tempFilePath(string $extension = 'tmp'): string
    {
        $extension = str_contains($extension, '.') ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
        $filename = uniqid('assets', true).'.'.$extension;
        $path = Path::temp($filename);

        if (($handle = fopen($path, 'wb')) === false) {
            throw new Exception('Could not create temp file: '.$path);
        }

        fclose($handle);

        return $path;
    }

    /**
     * Generates the URL for an asset.
     *
     * @param  string|null  $uri  Asset URI to use. Defaults to the filename.
     * @param  DateTimeInterface|null  $dateUpdated  last datetime the target of the url was updated, if known
     */
    public static function generateUrl(Asset $asset, ?string $uri = null, ?DateTimeInterface $dateUpdated = null): string
    {
        $volume = $asset->getVolume();
        $pathParts = explode('/', $asset->folderPath.($uri ?? $asset->getFilename()));
        $path = implode('/', array_map(rawurlencode(...), $pathParts));
        $url = $volume->sourceDisk()->url($path);

        if (Cms::config()->revAssetUrls) {
            return self::revUrl($url, $asset, $dateUpdated);
        }

        return $url;
    }

    /**
     * Returns revision query parameters that should be appended to as asset URL.
     *
     * @return array{v: string}
     */
    public static function revParams(Asset $asset, ?DateTimeInterface $dateUpdated = null): array
    {
        $v = [];

        $dateModified = $asset->dateModified;

        if ($dateUpdated && (! $dateModified || $dateUpdated->getTimestamp() > $dateModified->getTimestamp())) {
            $dateModified = $dateUpdated;
        }

        if ($dateModified) {
            $v[] = $dateModified->getTimestamp();
        }

        if ($asset->getHasFocalPoint()) {
            $fp = $asset->getFocalPoint();
            $v[] = $fp['x'];
            $v[] = $fp['y'];
        }

        return array_filter([
            'v' => implode(',', $v),
        ]);
    }

    /**
     * Appends revision parameters to a URL.
     *
     * @param  bool  $fsOnly  Only append a revision param if the URL begins with the asset’s filesystem URL
     */
    public static function revUrl(string $url, Asset $asset, ?DateTimeInterface $dateUpdated = null, bool $fsOnly = false): string
    {
        $revParams = static::revParams($asset, $dateUpdated);

        if (! $fsOnly) {
            return Url::urlWithParams($url, $revParams);
        }

        /** @var Collection<int, string> $baseUrls */
        $baseUrls = Collection::make();
        $volume = $asset->getVolume();

        if ($volume->sourceHasUrls()) {
            $baseUrls->push(self::diskBaseUrl($volume->sourceDisk()));
        }

        if ($volume->transformHasUrls()) {
            $baseUrls->push(self::diskBaseUrl($volume->transformDisk()));
        }

        $baseUrls = $baseUrls
            ->filter()
            ->unique();

        if ($baseUrls->doesntContain(fn (string $baseUrl): bool => str_starts_with($url, $baseUrl))) {
            return $url;
        }

        return Url::urlWithParams($url, $revParams);
    }

    private static function diskBaseUrl(FilesystemAdapter $disk): ?string
    {
        $probePath = '__craft_url_probe__';

        try {
            $probeUrl = $disk->url($probePath);
        } catch (Throwable) {
            return null;
        }

        foreach ([$probePath, rawurlencode($probePath)] as $suffix) {
            if (str_ends_with($probeUrl, $suffix)) {
                return Str::finish(substr($probeUrl, 0, -strlen($suffix)), '/');
            }
        }

        return Str::finish($probeUrl, '/');
    }

    /**
     * Clean an Asset's filename.
     *
     * @param  bool  $isFilename  if set to true (default), will separate extension
     *                            and clean the filename separately.
     * @param  bool  $preventPluginModifications  if set to true, will prevent plugins from modify
     */
    public static function prepareAssetName(string $name, bool $isFilename = true, bool $preventPluginModifications = false): string
    {
        if ($isFilename) {
            /** @var string $originalBaseName */
            $originalBaseName = pathinfo($name, PATHINFO_FILENAME);
            /** @var string $extension */
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            if ($extension !== '') {
                $extension = '.'.$extension;
            }
        } else {
            $originalBaseName = $name;
            $extension = '';
        }

        $generalConfig = Cms::config();
        $separator = $generalConfig->filenameWordSeparator;

        if (! is_string($separator)) {
            $separator = null;
        }

        $baseName = File::sanitizeFilename($originalBaseName, [
            'asciiOnly' => $generalConfig->convertFilenamesToAscii,
            'separator' => $separator,
        ]);

        if ($isFilename) {
            if (! $preventPluginModifications) {
                event($event = new SetAssetFilename($baseName, $originalBaseName, $extension));
                $baseName = $event->filename;
                $extension = $event->extension;
            }

            if ($baseName === '') {
                $baseName = '-';
            }
        }

        // Put them back together, but keep the full filename w/ extension from going over 255 chars
        return Str::limit($baseName, 255 - strlen($extension), '').$extension;
    }

    /**
     * Generates a default asset title based on its filename.
     *
     * @param  string  $filename  The asset's filename
     */
    public static function filename2Title(string $filename): string
    {
        $title = mb_ucfirst(implode(' ', Str::toWords($filename, false, true)));

        if (strlen($title) > 255) {
            return rtrim(substr($title, 255), ' ');
        }

        return $title;
    }

    /**
     * Mirrors a folder structure within a volume.
     *
     * @param  VolumeFolder  $sourceParentFolder  Folder whose nested folder structure should be mirrored.
     * @param  VolumeFolder  $destinationFolder  The destination folder
     * @param  array<string, int>  $targetTreeMap  map of relative path => existing folder ID
     * @return array<int, int> map of original folder ID => new folder ID
     */
    public static function mirrorFolderStructure(VolumeFolder $sourceParentFolder, VolumeFolder $destinationFolder, array $targetTreeMap = []): array
    {
        $sourceTree = Folders::getAllDescendantFolders($sourceParentFolder);
        $previousParent = $sourceParentFolder->getParent();
        $sourcePrefixLength = strlen($previousParent->path);
        $folderIdChanges = [];

        foreach ($sourceTree as $sourceFolder) {
            $relativePath = substr((string) $sourceFolder->path, $sourcePrefixLength);

            // If we have a target tree map, try to see if we should just point to an existing folder.
            if (! empty($targetTreeMap) && isset($targetTreeMap[$relativePath])) {
                $folderIdChanges[$sourceFolder->id] = $targetTreeMap[$relativePath];
            } else {
                $folder = new VolumeFolder;
                $folder->name = $sourceFolder->name;
                $folder->volumeId = $destinationFolder->volumeId;
                $folder->path = ltrim(rtrim((string) $destinationFolder->path, '/').'/'.$relativePath, '/');

                // Any and all parent folders should be already mirrored
                $folder->parentId = ($folderIdChanges[$sourceFolder->parentId] ?? $destinationFolder->id);
                Folders::createFolder($folder);

                $folderIdChanges[$sourceFolder->id] = $folder->id;
            }
        }

        return $folderIdChanges;
    }

    /**
     * Create an asset transfer list based on a list of assets and an array of
     * changing folder IDs.
     *
     * @param  list<Asset>  $assets  List of assets
     * @param  array<int, int>  $folderIdChanges  A map of folder ID changes
     * @return list<array{assetId:int, folderId:int, force:true}>
     */
    public static function fileTransferList(array $assets, array $folderIdChanges): array
    {
        $fileTransferList = [];

        // Build the transfer list for files
        foreach ($assets as $asset) {
            $newFolderId = $folderIdChanges[$asset->folderId];

            $fileTransferList[] = [
                'assetId' => $asset->id,
                'folderId' => $newFolderId,
                'force' => true,
            ];
        }

        return $fileTransferList;
    }

    /**
     * Returns a list of the supported file kinds.
     *
     * @return array<string, array{label:string, extensions:list<string>}> The supported file kinds
     */
    public static function getFileKinds(): array
    {
        return app(AssetFileKinds::class)->fileKinds();
    }

    /**
     * Returns a list of file kinds that are allowed to be uploaded.
     *
     * @return array<string, array{label:string, extensions:list<string>}> The allowed file kinds
     */
    public static function getAllowedFileKinds(): array
    {
        $allowedFileKinds = [];
        $allowedExtensions = array_flip(Cms::config()->allowedFileExtensions);

        foreach (static::getFileKinds() as $kind => $info) {
            foreach ($info['extensions'] as $extension) {
                if (isset($allowedExtensions[$extension])) {
                    $allowedFileKinds[$kind] = $info;

                    continue 2;
                }
            }
        }

        return $allowedFileKinds;
    }

    /**
     * Returns the label of a given file kind.
     */
    public static function getFileKindLabel(string $kind): string
    {
        return self::getFileKinds()[$kind]['label'] ?? FileKind::Unknown->value;
    }

    /**
     * Return a file's kind by a file's extension.
     *
     * @param  string  $file  The file name/path
     * @return string The file kind, or "unknown" if unknown.
     */
    public static function getFileKindByExtension(string $file): string
    {
        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);

            foreach (static::getFileKinds() as $kind => $info) {
                if (in_array($ext, $info['extensions'], true)) {
                    return $kind;
                }
            }
        }

        return FileKind::Unknown->value;
    }

    /**
     * Parses a file location in the format of `{folder:X}filename.ext` returns the folder ID + filename.
     *
     * @return array{int, string}
     *
     * @throws InvalidArgumentException if the file location is invalid
     */
    public static function parseFileLocation(string $location): array
    {
        if (! preg_match('/^{folder:(\d+)}(.+)$/', $location, $matches)) {
            throw new InvalidArgumentException('Invalid file location format: '.$location);
        }

        [, $folderId, $filename] = $matches;

        return [(int) $folderId, $filename];
    }

    /**
     * Returns the maximum allowed upload size in bytes per all config settings combined.
     */
    public static function getMaxUploadSize(): float|int
    {
        $maxUpload = PHP::sizeToBytes(ini_get('upload_max_filesize'));
        $maxPost = PHP::sizeToBytes(ini_get('post_max_size'));
        $memoryLimit = PHP::sizeToBytes(ini_get('memory_limit'));

        $uploadInBytes = min($maxUpload, $maxPost);

        if ($memoryLimit > 0) {
            $uploadInBytes = min($uploadInBytes, $memoryLimit);
        }

        $configLimit = Cms::config()->maxUploadFileSize;

        if ($configLimit) {
            return min($uploadInBytes, $configLimit);
        }

        return $uploadInBytes;
    }

    /**
     * Returns scaled width & height values for a maximum container size.
     *
     * @return array{int, int} The scaled width and height
     */
    public static function scaledDimensions(int $realWidth, int $realHeight, int $maxWidth, int $maxHeight): array
    {
        // Avoid division by 0 errors
        if ($realWidth === 0 || $realHeight === 0) {
            return [$maxWidth, $maxHeight];
        }

        $realRatio = $realWidth / $realHeight;
        $boundingRatio = $maxWidth / $maxHeight;

        if ($realRatio >= $boundingRatio) {
            $scaledWidth = $maxWidth;
            $scaledHeight = floor($realHeight * ($scaledWidth / $realWidth));
        } else {
            $scaledHeight = $maxHeight;
            $scaledWidth = floor($realWidth * ($scaledHeight / $realHeight));
        }

        return [(int) $scaledWidth, (int) $scaledHeight];
    }

    /**
     * Parses a srcset size (e.g. `100w` or `2x`).
     *
     * @return array{float, 'w'|'x'} An array of the size value and unit (`w` or `x`)
     *
     * @throws InvalidArgumentException if the size can’t be parsed
     */
    public static function parseSrcsetSize(mixed $size): array
    {
        if (is_numeric($size)) {
            $size .= 'w';
        }

        if (! is_string($size)) {
            throw new InvalidArgumentException('Invalid srcset size');
        }

        $size = strtolower($size);

        if (! preg_match('/^([\d.]+)([wx])$/', $size, $match)) {
            throw new InvalidArgumentException("Invalid srcset size: $size");
        }

        return [(float) $match[1], $match[2]];
    }

    /**
     * Save a file from a filesystem locally.
     *
     * @throws FilesystemException
     */
    public static function downloadFile(LaravelFilesystem $fs, string $uriPath, string $localPath): int
    {
        $stream = $fs->readStream($uriPath);

        if (! is_resource($stream)) {
            throw new FilesystemException("Unable to open $uriPath.");
        }

        $outputStream = fopen($localPath, 'wb');

        $bytes = stream_copy_to_stream($stream, $outputStream);

        fclose($stream);
        fclose($outputStream);

        return $bytes;
    }

    /**
     * Returns the SVG contents for an asset icon with a given extension.
     */
    public static function iconSvg(string $extension): string
    {
        if (! preg_match('/^\w+$/', $extension)) {
            throw new InvalidArgumentException("$extension isn’t a valid file extension.");
        }

        $path = Path::assetsIcons(strtolower($extension));

        if (file_exists($path)) {
            return $path;
        }

        $svg = file_get_contents(Path::resources('public/images/thumbs/file.svg'));

        $extLength = strlen($extension);

        [$textSize, $extension] = match (true) {
            $extLength <= 3 => ['19', $extension],
            $extLength === 4 => ['16', $extension],
            default => ['15', substr($extension, 0, 3).'…']
        };

        $textNode = Html::tag('text', strtoupper($extension), [
            'x' => 50,
            'y' => 73,
            'text-anchor' => 'middle',
            'font-family' => 'sans-serif',
            'fill' => 'hsl(210, 10%, 47%)',
            'font-size' => $textSize,
        ]);

        return Html::appendToTag($svg, $textNode);
    }

    /**
     * Returns whether the given filesystem is used to store temporary asset uploads.
     */
    public static function isTempUploadFs(FsInterface $fs): bool
    {
        if ($fs instanceof Temp) {
            return true;
        }

        if (! $fs->handle) {
            return false;
        }

        $target = self::normalizedTempUploadTarget();

        return $target !== null && $fs->handle === $target;
    }

    private static function normalizedTempUploadTarget(): ?string
    {
        $handle = Env::parse(Cms::config()->tempAssetUploadFs);

        if (! is_string($handle) || $handle === '') {
            return null;
        }

        return Filesystems::resolve($handle)?->handle;
    }

    /**
     * Resolves a possibly dynamic subpath for a given element, and returns the rendered subpath and
     * matching volume folder (if one exists).
     *
     * @return array{0:string,1:VolumeFolder|null}
     *
     * @throws Exception
     * @throws InvalidSubpathException
     */
    public static function resolveSubpath(Volume $volume, ?string $subpath, ?ElementInterface $element = null): array
    {
        $rootFolder = Folders::getRootFolderByVolumeId($volume->id);

        // Are we looking for the root folder?
        $subpath = trim($subpath ?? '', '/');
        if ($subpath === '') {
            return [$subpath, $rootFolder];
        }

        if (str_contains($subpath, '{')) {
            // Prepare the path by parsing tokens and normalizing slashes.
            try {
                if ($element?->duplicateOf) {
                    $element = $element->duplicateOf->getCanonical();
                }
                $renderedSubpath = renderObjectTemplate($subpath, $element);
            } catch (RuntimeError $e) {
                throw new InvalidSubpathException($subpath, previous: $e);
            }

            // Did any of the tokens return null?
            if (
                $renderedSubpath === '' ||
                trim($renderedSubpath, '/') !== $renderedSubpath ||
                str_contains($renderedSubpath, '//') ||
                collect(explode('/', $renderedSubpath))
                    ->contains(fn (string $segment) => ElementHelper::isTempSlug($segment))
            ) {
                throw new InvalidSubpathException($subpath);
            }

            // Sanitize the subpath
            $segments = array_filter(explode('/', $renderedSubpath), fn (string $segment): bool => $segment !== ':ignore:');
            $segments = array_map(fn (string $segment): string => File::sanitizeFilename($segment, [
                'asciiOnly' => Cms::config()->convertFilenamesToAscii,
            ]), $segments);
            $subpath = implode('/', $segments);
        }

        $folder = Folders::findFolder([
            'volumeId' => $volume->id,
            'path' => $subpath.'/',
        ]);

        return [$subpath, $folder];
    }
}
