<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\SetAssetFilename;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException;
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

use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\renderObjectTemplate;

/**
 * @since 6.0.0
 */
class AssetsHelper
{
    public const string INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

    /**
     * The most characters a file’s preview text keeps, which is more than its icon shows.
     *
     * @see previewText()
     *
     * @internal
     */
    public const int PREVIEW_TEXT_LENGTH = 1000;

    /**
     * How many bytes to read from the start of a file for its preview text: enough for
     * {@see PREVIEW_TEXT_LENGTH} characters of up to four bytes each.
     *
     * @see previewText()
     *
     * @internal
     */
    public const int PREVIEW_TEXT_SOURCE_LENGTH = self::PREVIEW_TEXT_LENGTH * 4;

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

        if (mb_strlen($title) > 255) {
            return rtrim(mb_substr($title, 0, 255), ' ');
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
        $sourcePrefixLength = strlen((string) $previousParent->path);
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

    /** The asset limit, independent of the size of individual upload requests. */
    public static function getMaxAssetUploadSize(): int
    {
        $limit = PHP::sizeToBytes(Cms::config()->maxUploadFileSize);

        return $limit > 0 ? $limit : PHP_INT_MAX;
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
     * Returns the SVG contents for an asset icon with a given extension, in
     * the light or dark variant for the control panel theme that's showing,
     * unless `$dark` says which.
     *
     * The paper is laid out in percentages of the icon's box, while its corner
     * radius, outline and shadow are in pixels, so they look the same at any
     * size. The outline's outer corners match the image thumbnails and chips
     * around it, through `--c-thumbnail-image-radius`.
     */
    public static function iconSvg(string $extension, ?bool $dark = null): string
    {
        if (! preg_match('/^\w+$/', $extension)) {
            throw new InvalidArgumentException("$extension isn’t a valid file extension.");
        }

        $path = Path::assetsIcons(strtolower($extension));

        if (file_exists($path)) {
            return $path;
        }

        $dark ??= Theme::resolveFor(currentUserElement()) === Theme::Dark;
        $radius = 'var(--c-thumbnail-image-radius, 4px)';
        $shapeRadius = "max(0px, calc($radius - 1px))";
        $ringRadius = "max(0px, calc($radius - 0.5px))";
        $paper = ['x' => '14.5%', 'y' => '4%', 'width' => '71%', 'height' => '92%', 'rx' => 4];

        $paperNode = Html::tag('rect', '', [
            ...$paper,
            'fill' => $dark ? '#1a2231' : '#fff',
            'style' => sprintf(
                'rx: %s; %sfilter: drop-shadow(0 1px 1.5px rgb(0 0 0 / %s))',
                $shapeRadius, $dark ? 'fill: var(--c-color-black-fill-quiet, #1a2231); ' : '', $dark ? '0.6' : '0.18',
            ),
        ]);
        $ringNode = Html::tag('rect', '', [
            ...$paper,
            'fill' => 'none',
            'stroke' => $dark ? '#fff' : '#000',
            'stroke-opacity' => $dark ? 0.25 : 0.18,
            'style' => sprintf(
                'x: calc(14.5%% - 0.5px); y: calc(4%% - 0.5px); width: calc(71%% + 1px); height: calc(92%% + 1px); rx: %s',
                $ringRadius,
            ),
        ]);

        $extLength = strlen($extension);

        [$textSize, $extension] = match (true) {
            $extLength <= 3 => ['24', $extension],
            $extLength === 4 => ['20', $extension],
            default => ['18', substr($extension, 0, 3).'…']
        };

        $quietText = $dark ? '#a6b2c2' : '#5c6982';
        $textNode = Html::tag('svg', Html::tag('text', strtoupper($extension), [
            'x' => 50,
            'y' => 82,
            'text-anchor' => 'middle',
            'font-family' => 'system-ui, sans-serif',
            'font-weight' => 600,
            'fill' => $quietText,
            'font-size' => $textSize,
            'style' => "fill: var(--c-text-quiet, $quietText)",
        ]), ['viewBox' => '0 0 100 100']);

        return Html::tag('svg', $paperNode.$ringNode.$textNode, [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'width' => '100%',
            'height' => '100%',
            'overflow' => 'visible',
        ]);
    }

    /**
     * Returns the start of a text file’s contents, up to {@see PREVIEW_TEXT_LENGTH} characters, for
     * {@see Asset::$previewText}. It’s an empty string if there’s nothing to show, which still marks the file as read.
     *
     * @param  string  $contents  The start of the file, such as its first {@see PREVIEW_TEXT_SOURCE_LENGTH} bytes.
     *
     * @internal
     */
    public static function previewText(string $contents): string
    {
        $text = mb_scrub(mb_strcut($contents, 0, self::PREVIEW_TEXT_SOURCE_LENGTH, 'UTF-8'), 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Control characters other than line breaks and tabs have nothing to show
        $text = (string) preg_replace('/[^\P{C}\n\t]/u', '', $text);

        return rtrim(mb_substr($text, 0, self::PREVIEW_TEXT_LENGTH));
    }

    /**
     * Returns the SVG contents for a closer look at a text file than its icon gives: the top of the page, zoomed in
     * and cut off at the bottom, with the file’s preview text wrapping across it and fading toward the cut. It has no extension label, and fills a 240×160 box.
     *
     * Like {@see iconSvg()}, it comes in a light or dark variant for the control panel theme that’s showing, unless
     * `$dark` says which.
     *
     * @internal
     */
    public static function previewTextSvg(string $previewText, ?bool $dark = null): string
    {
        $dark ??= Theme::resolveFor(currentUserElement()) === Theme::Dark;
        $quietText = $dark ? '#a6b2c2' : '#5c6982';
        // The page runs past the bottom of the box, so its bottom edge is never seen
        $page = ['x' => 12, 'y' => 12, 'width' => 216, 'height' => 300, 'rx' => 6];

        $paperNode = Html::tag('rect', '', [
            ...$page,
            'fill' => $dark ? '#1a2231' : '#fff',
            'style' => sprintf(
                '%sfilter: drop-shadow(0 2px 3px rgb(0 0 0 / %s))',
                $dark ? 'fill: var(--c-color-black-fill-quiet, #1a2231); ' : '', $dark ? '0.6' : '0.18',
            ),
        ]);
        $ringNode = Html::tag('rect', '', [
            'x' => 11.5,
            'y' => 11.5,
            'width' => 217,
            'height' => 301,
            'rx' => 6.5,
            'fill' => 'none',
            'stroke' => $dark ? '#fff' : '#000',
            'stroke-opacity' => $dark ? 0.25 : 0.18,
        ]);

        $fade = 'linear-gradient(#000 50%, rgb(0 0 0 / 30%))';
        // Exactly this many lines fill the text area, so it's cut off between two lines rather than through one. It
        // stops a little above the bottom of the box, leaving a gap below the last line.
        $lines = 11;
        $textHeight = 131;
        $lineHeight = $textHeight / $lines;
        $textNode = Html::tag('foreignObject', Html::tag('div', Html::encode($previewText), [
            'xmlns' => 'http://www.w3.org/1999/xhtml',
            'style' => [
                'height' => '100%',
                'overflow' => 'hidden',
                'margin' => 0,
                'font-family' => 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
                'font-size' => sprintf('%.2fpx', $lineHeight / 1.3),
                'line-height' => sprintf('%.4fpx', $lineHeight),
                'white-space' => 'pre-wrap',
                'overflow-wrap' => 'anywhere',
                'tab-size' => 4,
                'color' => "var(--c-text-quiet, $quietText)",
                '-webkit-mask-image' => $fade,
                'mask-image' => $fade,
            ],
        ]), [
            'x' => 26,
            'y' => 26,
            'width' => 188,
            'height' => $textHeight,
        ]);

        return Html::tag('svg', $paperNode.$ringNode.$textNode, [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'viewBox' => '0 0 240 160',
            'width' => '100%',
            'height' => '100%',
            'overflow' => 'visible',
            // Only the bottom is cut off, so the shadow still shows on the other sides
            'style' => 'clip-path: inset(-20px -20px 0 -20px)',
        ]);
    }

    /**
     * Returns the SVG contents for a folder icon, in the light or dark variant
     * for the control panel theme that's showing, unless `$dark` says which.
     *
     * Like {@see self::iconSvg()}, it's laid out in percentages of the icon's
     * box, with its corners, shadow and (in the dark variant) outline in
     * pixels.
     */
    public static function folderIconSvg(?bool $dark = null): string
    {
        $dark ??= Theme::resolveFor(currentUserElement()) === Theme::Dark;
        $radius = 'var(--c-thumbnail-image-radius, 4px)';
        $shapeRadius = "max(0px, calc($radius - 1px))";
        $ringRadius = "max(0px, calc($radius - 0.5px))";
        $back = ['x' => '8%', 'y' => '20%', 'width' => '84%', 'height' => '66%', 'rx' => 4];
        $tab = ['x' => '8%', 'y' => '13%', 'width' => '36%', 'height' => '13%', 'rx' => 3];
        $tabTop = 'min(13%, calc(20% - 2.25px))';
        $tabStyle = "y: $tabTop; height: calc(26% - $tabTop); rx: $shapeRadius";
        $backColor = $dark ? '#172560' : '#5b96f6';

        $outlineNodes = '';
        if ($dark) {
            $ringNodes = Html::tag('g', implode('', [
                Html::tag('rect', '', [
                    ...$tab,
                    'style' => "x: calc(8% - 0.5px); y: calc($tabTop - 0.5px); width: calc(36% + 1px); height: calc(26% - $tabTop + 1px); rx: $ringRadius",
                ]),
                Html::tag('rect', '', [
                    ...$back,
                    'style' => sprintf(
                        'x: calc(8%% - 0.5px); y: calc(20%% - 0.5px); width: calc(84%% + 1px); height: calc(66%% + 1px); rx: %s',
                        $ringRadius,
                    ),
                ]),
            ]), [
                'fill' => 'none',
                'stroke' => '#fff',
                'opacity' => 0.25,
            ]);
            $seamCoverNodes = Html::tag('g', implode('', [
                Html::tag('rect', '', [...$tab, 'style' => $tabStyle]),
                Html::tag('rect', '', [
                    'x' => '8%',
                    'y' => '20%',
                    'width' => '37%',
                    'height' => '7%',
                    'style' => 'width: calc(36% + 2px); height: min(calc(6% + 2px), 10%)',
                ]),
            ]), ['fill' => $backColor]);
            $outlineNodes = $ringNodes.$seamCoverNodes;
        }
        $backNodes = Html::tag('g', implode('', [
            Html::tag('rect', '', [...$tab, 'style' => $tabStyle]),
            Html::tag('rect', '', [...$back, 'style' => "rx: $shapeRadius"]),
        ]), [
            'fill' => $backColor,
            'style' => sprintf('filter: drop-shadow(0 1px 1.5px rgb(0 0 0 / %s))', $dark ? '0.6' : '0.18'),
        ]);
        $frontNode = Html::tag('rect', '', [
            'x' => '8%',
            'y' => '30%',
            'width' => '84%',
            'height' => '56%',
            'rx' => 4,
            'fill' => $dark ? '#1b2f8c' : '#8ab5f8',
            'style' => sprintf(
                'rx: %s; filter: drop-shadow(0 -0.5px 1px rgb(0 0 0 / %s))',
                $shapeRadius, $dark ? '0.35' : '0.15',
            ),
        ]);

        $frontEdgeNode = Html::tag('rect', '', [
            'x' => '12%',
            'y' => '30%',
            'width' => '76%',
            'height' => 1,
            'fill' => '#fff',
            'fill-opacity' => $dark ? 0.2 : 0.6,
            'style' => sprintf('x: calc(8%% + %1$s); width: calc(84%% - 2 * %1$s)', $shapeRadius),
        ]);

        return Html::tag('svg', $backNodes.$frontNode.$frontEdgeNode.$outlineNodes, [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'width' => '100%',
            'height' => '100%',
            'overflow' => 'visible',
        ]);
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
