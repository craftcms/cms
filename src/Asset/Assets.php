<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Craft;
use craft\assetpreviews\Image as ImagePreview;
use craft\assetpreviews\Pdf;
use craft\assetpreviews\Text;
use craft\assetpreviews\Video;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AfterReplaceAsset;
use CraftCms\Cms\Asset\Events\BeforeReplaceAsset;
use CraftCms\Cms\Asset\Events\DefineThumbUrl;
use CraftCms\Cms\Asset\Events\RegisterPreviewHandler;
use CraftCms\Cms\Asset\Exceptions\AssetOperationException;
use CraftCms\Cms\Asset\Exceptions\VolumeException;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\FallbackTransformer;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

use function CraftCms\Cms\t;

#[Singleton]
final class Assets
{
    /** @var VolumeFolder[] */
    private array $userTempFolders = [];

    public function __construct(
        private readonly Folders $folders,
    ) {}

    public function getAssetById(int $assetId, ?int $siteId = null): ?Asset
    {
        return Craft::$app->getElements()->getElementById($assetId, Asset::class, $siteId);
    }

    public function getTotalAssets(mixed $criteria = null): int
    {
        if ($criteria instanceof AssetQuery) {
            return $criteria->count();
        }

        $query = Asset::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query->count();
    }

    public function replaceAssetFile(Asset $asset, string $pathOnServer, string $filename, ?string $mimeType = null): void
    {
        event($event = new BeforeReplaceAsset(
            asset: $asset,
            replaceWith: $pathOnServer,
            filename: $filename,
        ));

        $filename = $event->filename;

        $asset->tempFilePath = $pathOnServer;
        $asset->newFilename = $filename;
        $asset->setMimeType(FileHelper::getMimeType($pathOnServer, checkExtension: false) ?? $mimeType);
        $asset->uploaderId = Auth::user()?->id;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_REPLACE);
        Craft::$app->getElements()->saveElement($asset);

        event(new AfterReplaceAsset(
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
            $asset->setScenario(Asset::SCENARIO_FILEOPS);
        } else {
            $asset->setScenario(Asset::SCENARIO_MOVE);
        }

        return Craft::$app->getElements()->saveElement($asset);
    }

    public function getThumbUrl(Asset $asset, int $width, ?int $height = null, bool $iconFallback = true): ?string
    {
        $height ??= $width;

        event($event = new DefineThumbUrl(
            asset: $asset,
            width: $width,
            height: $height,
        ));

        if ($event->url !== null) {
            return $event->url;
        }

        $extension = $asset->getExtension();

        if (! Image::canManipulateAsImage($extension)) {
            return $iconFallback ? AssetsHelper::iconUrl($extension) : null;
        }

        $transform = Craft::createObject(ImageTransform::class, [
            'width' => $width,
            'height' => $height,
            'mode' => 'crop',
        ]);

        $url = $asset->getUrl($transform);

        if (! $url) {
            $transform->setTransformer(FallbackTransformer::class);
            $url = $asset->getUrl($transform);
        }

        if ($url === null) {
            return $iconFallback ? AssetsHelper::iconUrl($extension) : null;
        }

        return AssetsHelper::revUrl($url, $asset, fsOnly: true);
    }

    /**
     * @throws NotSupportedException if the asset's volume doesn't have a filesystem with public URLs
     */
    public function getImagePreviewUrl(Asset $asset, int $maxWidth, int $maxHeight): string
    {
        $isWebSafe = Image::isWebSafe($asset->getExtension());
        $originalWidth = (int) $asset->getWidth();
        $originalHeight = (int) $asset->getHeight();
        [$width, $height] = AssetsHelper::scaledDimensions((int) $asset->getWidth(), (int) $asset->getHeight(), $maxWidth, $maxHeight);

        if (
            ! $isWebSafe ||
            ! $asset->getVolume()->getFs()->hasUrls ||
            $originalWidth > $width ||
            $originalHeight > $height
        ) {
            $transform = Craft::createObject([
                'class' => ImageTransform::class,
                'width' => $width,
                'height' => $height,
                'mode' => 'crop',
            ]);
        } else {
            $transform = null;
        }

        if (! $url = $asset->getUrl($transform, true)) {
            throw new NotSupportedException('A preview URL couldn’t be generated for the asset.');
        }

        return AssetsHelper::revUrl($url, $asset, fsOnly: true);
    }

    /**
     * @throws AssetOperationException
     * @throws InvalidConfigException
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
            $timestamp = DateTimeHelper::currentUTCDateTime()->format('Y-m-d-His');
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
                throw new AssetOperationException(t('Could not find a suitable replacement filename for "{filename}".', [
                    'filename' => $originalFilename,
                ]));
            }

            $increment++;
        }

        return $newFilename;
    }

    public function getAssetPreviewHandler(Asset $asset): ?AssetPreviewHandlerInterface
    {
        event($event = new RegisterPreviewHandler(asset: $asset));

        if ($event->previewHandler instanceof AssetPreviewHandlerInterface) {
            return $event->previewHandler;
        }

        return match ($asset->kind) {
            Asset::KIND_IMAGE => new ImagePreview($asset),
            Asset::KIND_PDF => new Pdf($asset),
            Asset::KIND_VIDEO => new Video($asset),
            Asset::KIND_HTML, Asset::KIND_JAVASCRIPT, Asset::KIND_JSON, Asset::KIND_PHP, Asset::KIND_TEXT, Asset::KIND_XML => new Text($asset),
            default => null,
        };
    }

    /**
     * @throws InvalidConfigException
     */
    public function getTempAssetUploadFs(): FsInterface
    {
        $handle = Env::parse(Cms::config()->tempAssetUploadFs);

        if (! $handle) {
            return new Temp;
        }

        return Filesystems::resolve($handle)
            ?? throw new InvalidConfigException("The tempAssetUploadFs config setting is set to an invalid filesystem value: $handle");
    }

    /**
     * @throws InvalidConfigException
     */
    public function getTempAssetUploadDisk(): FilesystemAdapter
    {
        $handle = Env::parse(Cms::config()->tempAssetUploadFs);

        if (! $handle) {
            return Storage::build([ // @phpstan-ignore return.type
                'driver' => 'local',
                'root' => Craft::$app->getPath()->getTempAssetUploadsPath(),
            ]);
        }

        return Storage::disk(
            Filesystems::resolveDiskName($handle)
                ?? throw new InvalidConfigException("The tempAssetUploadFs config setting is set to an invalid filesystem value: $handle")
        );
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
        $user ??= Auth::user();
        $cacheKey = $user->id ?? '__GUEST__';

        if (isset($this->userTempFolders[$cacheKey])) {
            return $this->userTempFolders[$cacheKey];
        }

        $folders = $this->folders;

        if ($user) {
            $folderName = "user_{$user->id}";
        } elseif (app()->runningInConsole()) {
            $folderName = 'temp_'.sha1((string) time());
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
