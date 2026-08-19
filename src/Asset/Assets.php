<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\AssetReplaced;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Events\PreviewHandlerResolving;
use CraftCms\Cms\Asset\Events\ThumbUrlResolving;
use CraftCms\Cms\Asset\Exceptions\AssetNotPreviewableException;
use CraftCms\Cms\Asset\Exceptions\AssetOperationException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Asset\PreviewHandlers\Image as ImagePreview;
use CraftCms\Cms\Asset\PreviewHandlers\Pdf;
use CraftCms\Cms\Asset\PreviewHandlers\Text;
use CraftCms\Cms\Asset\PreviewHandlers\Video;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems as FilesystemsService;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

#[Scoped]
class Assets
{
    /** @var array<int|string, VolumeFolder> */
    private array $userTempFolders = [];

    public function __construct(
        private readonly Folders $folders,
        private readonly Elements $elements,
        private readonly AssetTransforms $assetTransforms,
    ) {}

    public function getAssetById(int $assetId, ?int $siteId = null): ?Asset
    {
        return $this->elements->getElementById($assetId, Asset::class, $siteId);
    }

    public function getTotalAssets(mixed $criteria = null): int
    {
        if ($criteria instanceof AssetQuery) {
            return $criteria->count();
        }

        $query = Asset::find();

        if ($criteria) {
            Typecast::configure($query, $criteria);
        }

        return $query->count();
    }

    public function replaceAssetFile(Asset $asset, string $pathOnServer, string $filename, ?string $mimeType = null): void
    {
        event($event = new AssetReplacing(
            asset: $asset,
            replaceWith: $pathOnServer,
            filename: $filename,
        ));

        $filename = $event->filename;

        $asset->tempFilePath = $pathOnServer;
        $asset->newFilename = $filename;
        $asset->setMimeType(File::getMimeType($pathOnServer, checkExtension: false) ?? $mimeType);
        $asset->uploaderId = currentUser()?->getCraftUserId();
        $asset->avoidFilenameConflicts = true;
        $asset->ruleset->useScenario(AssetRules::SCENARIO_REPLACE);
        $this->elements->saveElement($asset);

        event(new AssetReplaced(
            asset: $asset,
            filename: $filename,
        ));
    }

    public function moveAsset(Asset $asset, VolumeFolder $folder, string $filename = ''): bool
    {
        $folderChanging = $asset->folderId != $folder->id;
        $filenameChanging = $filename !== '' && $filename !== $asset->getFilename();

        if (! $folderChanging && ! $filenameChanging) {
            return true;
        }

        if ($folderChanging) {
            $asset->newFolderId = $folder->id;
        }

        if ($filenameChanging) {
            $asset->newFilename = $filename;
            $asset->ruleset->useScenario(AssetRules::SCENARIO_FILEOPS);
        } else {
            $asset->ruleset->useScenario(AssetRules::SCENARIO_MOVE);
        }

        return $this->elements->saveElement($asset);
    }

    public function getThumbUrl(Asset $asset, int $width, ?int $height = null, bool $iconFallback = true): ?string
    {
        $height ??= $width;

        event($event = new ThumbUrlResolving(
            asset: $asset,
            width: $width,
            height: $height,
        ));

        if ($event->url !== null) {
            return $event->url;
        }

        $extension = $asset->getExtension();

        try {
            $url = $this->assetTransforms->transform($asset, [
                'width' => $width,
                'height' => $height,
                'mode' => 'crop',
            ], $event->transformSettings)->url;
        } catch (NotSupportedException) {
            return $iconFallback ? Url::actionUrl('assets/icon', [
                'extension' => $extension,
            ]) : null;
        }

        return AssetsHelper::revUrl($url, $asset, fsOnly: true);
    }

    /**
     * @throws AssetNotPreviewableException if a preview URL can't be generated for the asset
     */
    public function getImagePreviewUrl(Asset $asset, int $maxWidth, int $maxHeight): string
    {
        $isWebSafe = ImageHelper::isWebSafe($asset->getExtension());
        $originalWidth = (int) $asset->getWidth();
        $originalHeight = (int) $asset->getHeight();
        [$width, $height] = AssetsHelper::scaledDimensions((int) $asset->getWidth(), (int) $asset->getHeight(), $maxWidth, $maxHeight);

        if (
            ! $isWebSafe ||
            ! $asset->getVolume()->sourceHasUrls() ||
            $originalWidth > $width ||
            $originalHeight > $height
        ) {
            $transform = [
                'width' => $width,
                'height' => $height,
                'mode' => 'crop',
            ];
        } else {
            $transform = null;
        }

        try {
            $url = $transform !== null
                ? $this->assetTransforms->transform($asset, $transform, ['generateBeforePageLoad' => true])->url
                : $asset->getUrl();
        } catch (NotSupportedException) {
            return Url::actionUrl('assets/icon', [
                'extension' => $asset->getExtension(),
            ]);
        }

        if (! $url) {
            throw new AssetNotPreviewableException("A preview URL couldn't be generated for the asset.");
        }

        return AssetsHelper::revUrl($url, $asset, fsOnly: true);
    }

    /**
     * @throws AssetOperationException
     * @throws RuntimeException
     */
    public function getNameReplacementInFolder(string $originalFilename, int $folderId): string
    {
        $folder = $this->folders->getFolderById($folderId);

        if (! $folder) {
            throw new InvalidArgumentException("Invalid folder ID: $folderId");
        }

        $volume = $folder->getVolume();
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);

        $buildFilename = function (string $name, string $suffix = '') use ($extension) {
            $maxLength = 255 - strlen($suffix);
            if ($extension !== '') {
                $maxLength -= strlen($extension) + 1;
            }
            if (strlen($name) > $maxLength) {
                $name = substr($name, 0, $maxLength);
            }

            return $name.$suffix;
        };

        $baseFileName = $buildFilename(pathinfo($originalFilename, PATHINFO_FILENAME));

        $dbFileList = DB::table(new Alias(Table::ASSETS, 'assets'))
            ->join(new Alias(Table::ELEMENTS, 'elements'), 'elements.id', 'assets.id')
            ->where('assets.folderId', $folderId)
            ->whereNull('elements.dateDeleted')
            ->whereLike('assets.filename', $baseFileName.'%.'.$extension)
            ->pluck('assets.filename');

        $potentialConflicts = [];

        foreach ($dbFileList as $filename) {
            $potentialConflicts[mb_strtolower((string) $filename)] = true;
        }

        $canUse = static fn ($filenameToTest) => ! isset($potentialConflicts[mb_strtolower((string) $filenameToTest)])
            && ! $volume->sourceDisk()->exists($folder->path.$filenameToTest);

        if ($canUse($originalFilename)) {
            return $originalFilename;
        }

        if (preg_match('/.*_\d{4}-\d{2}-\d{2}-\d{6}$/', $baseFileName, $matches)) {
            $base = $baseFileName;
        } else {
            $timestamp = now('UTC')->format('Y-m-d-His');
            $base = $buildFilename($baseFileName, '_'.$timestamp);
        }

        $base = $buildFilename($base, sprintf('_%s', Str::random(4)));

        $increment = 0;

        while (true) {
            $suffix = $increment ? "_$increment" : '';
            $newFilename = $buildFilename($base, $suffix).($extension !== '' ? ".$extension" : '');

            if ($canUse($newFilename)) {
                break;
            }

            if ($increment === 50) {
                throw new AssetOperationException(t('Could not find a suitable replacement filename for “{filename}”.', [
                    'filename' => $originalFilename,
                ]));
            }

            $increment++;
        }

        return $newFilename;
    }

    public function getAssetPreviewHandler(Asset $asset): ?AssetPreviewHandlerInterface
    {
        event($event = new PreviewHandlerResolving(asset: $asset));

        if ($event->previewHandler instanceof AssetPreviewHandlerInterface) {
            return $event->previewHandler;
        }

        return match ($asset->kind) {
            FileKind::Image->value => new ImagePreview($asset),
            FileKind::Pdf->value => new Pdf($asset),
            FileKind::Video->value => new Video($asset),
            FileKind::Html->value, FileKind::Javascript->value, FileKind::Json->value, FileKind::Php->value, FileKind::Text->value, FileKind::Xml->value => new Text($asset),
            default => null,
        };
    }

    /**
     * @throws RuntimeException
     */
    public function getTempAssetUploadFs(): FsInterface
    {
        $handle = Env::parse(Cms::config()->tempAssetUploadFs);

        if (! $handle) {
            return new Temp([
                'handle' => 'disk:'.FilesystemsService::TEMP_ASSET_DISK,
            ]);
        }

        return Filesystems::resolve($handle)
            ?? throw new RuntimeException("The tempAssetUploadFs config setting is set to an invalid filesystem value: $handle");
    }

    /**
     * @throws RuntimeException
     */
    public function getTempAssetUploadDisk(): FilesystemAdapter
    {
        $handle = Env::parse(Cms::config()->tempAssetUploadFs);

        return Filesystems::disk($handle ?: 'disk:'.FilesystemsService::TEMP_ASSET_DISK);
    }

    public function createTempAssetQuery(): AssetQuery
    {
        return new AssetQuery()->volumeId(':empty:');
    }

    /**
     * @throws VolumeException
     */
    public function getUserTemporaryUploadFolder(?User $user = null): VolumeFolder
    {
        $user ??= currentUserElement();
        $cacheKey = $user->id ?? '__GUEST__';

        if (isset($this->userTempFolders[$cacheKey])) {
            return $this->userTempFolders[$cacheKey];
        }

        $folders = $this->folders;

        if ($user) {
            $folderName = "user_{$user->id}";
        } elseif (app()->runningInConsole()) {
            $folderName = 'temp_'.sha1((string) now()->getTimestamp());
        } else {
            $folderName = 'user_'.sha1(session()->id());
        }

        $volumeTopFolder = $folders->findFolder([
            'volumeId' => ':empty:',
            'parentId' => ':empty:',
        ]);

        if (! $volumeTopFolder) {
            $volumeTopFolder = new VolumeFolder;
            $volumeTopFolder->name = t('Temporary Uploads');
            $folders->storeFolderModel($volumeTopFolder);
        }

        $folder = $folders->findFolder([
            'name' => $folderName,
            'parentId' => $volumeTopFolder->id,
        ]);

        if (! $folder) {
            $folder = new VolumeFolder;
            $folder->parentId = $volumeTopFolder->id;
            $folder->name = $folderName;
            $folder->path = $folderName.'/';
            $folders->storeFolderModel($folder);
        }

        $disk = $this->getTempAssetUploadDisk();

        try {
            if (! $disk->directoryExists($folderName) && ! $disk->makeDirectory($folderName)) {
                throw new VolumeException('Unable to create directory for temporary uploads.');
            }
        } catch (Throwable) {
            throw new VolumeException('Unable to create directory for temporary uploads.');
        }

        $folder->name = t('Temporary Uploads');

        return $this->userTempFolders[$cacheKey] = $folder;
    }

    public function reset(): void
    {
        $this->userTempFolders = [];
    }
}
