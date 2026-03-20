<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Concerns\EnforcesVolumePermissions;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Uri;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

use function CraftCms\Cms\maxPowerCaptain;
use function CraftCms\Cms\t;

readonly class ActionController
{
    use EnforcesVolumePermissions;
    use RespondsWithFlash;

    public function __construct(
        private Assets $assets,
        private Folders $folders,
    ) {}

    public function deleteAsset(Request $request): Response
    {
        $assetId = $request->input('sourceId') ?? $request->input('assetId');

        abort_if(empty($assetId), 400, 'Missing asset ID');

        $asset = $this->assets->getAssetById($assetId);

        abort_if(! $asset, 400, "Invalid asset ID: $assetId");

        $this->requireVolumePermissionByAsset('deleteAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerAssets', $asset);

        $success = Craft::$app->getElements()->deleteElement($asset);

        if (! $success) {
            return $this->asModelFailure(
                $asset,
                t('Couldn’t delete {type}.', [
                    'type' => Asset::lowerDisplayName(),
                ]),
                'asset'
            );
        }

        return $this->asModelSuccess(
            $asset,
            t('{type} deleted.', [
                'type' => Asset::displayName(),
            ]),
            'asset',
        );
    }

    public function moveAsset(Request $request): Response
    {
        $request->validate([
            'assetId' => ['required'],
        ]);

        $assetId = $request->input('assetId');
        $asset = $this->assets->getAssetById($assetId);

        abort_if($asset === null, 400, 'The Asset cannot be found');

        $folder = $this->folders->getFolderById($request->input('folderId', $asset->folderId));

        abort_if($folder === null, 400, 'The folder cannot be found');

        $filename = $request->input('filename') ?? $asset->getFilename();

        $this->requireVolumePermissionByFolder('saveAssets', $folder);
        $this->requireVolumePermissionByAsset('deleteAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('savePeerAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('deletePeerAssets', $asset);

        if ($request->boolean('force')) {
            /** @var Asset|null $conflictingAsset */
            $conflictingAsset = Asset::find()
                ->select(['elements.id'])
                ->folderId($folder->id)
                ->filename(Query::escapeParam($asset->getFilename()))
                ->one();

            if ($conflictingAsset) {
                Craft::$app->getElements()->mergeElementsByIds($conflictingAsset->id, $asset->id);
            } else {
                $volume = $folder->getVolume();
                $volume->sourceDisk()->delete(rtrim($folder->path, '/').'/'.$asset->getFilename());
            }
        }

        $result = $this->assets->moveAsset($asset, $folder, $filename);

        if (! $result) {
            [, $filename] = AssetsHelper::parseFileLocation($asset->newLocation);

            return new JsonResponse([
                'conflict' => $asset->errors()->first('newLocation'),
                'suggestedFilename' => $asset->suggestedFilename,
                'filename' => $filename,
                'assetId' => $asset->id,
            ]);
        }

        return $this->asSuccess();
    }

    public function downloadAsset(Request $request): Response
    {
        $request->validate([
            'assetId' => ['required'],
        ]);

        $assetIds = $request->input('assetId');
        /** @var Asset[] $assets */
        $assets = Asset::find()
            ->id($assetIds)
            ->all();

        abort_if(empty($assets), 400, t('The asset you’re trying to download does not exist.'));

        foreach ($assets as $asset) {
            $this->requireVolumePermissionByAsset('viewAssets', $asset);
            $this->requirePeerVolumePermissionByAsset('viewPeerAssets', $asset);
        }

        // If only one asset was selected, send it back unzipped
        if (count($assets) === 1) {
            $asset = reset($assets);

            return response()->streamDownload(function () use ($asset) {
                $stream = $asset->getStream();
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, $asset->getFilename(), [
                'Content-Type' => $asset->getMimeType(),
                'Content-Length' => $asset->size,
            ]);
        }

        // Otherwise create a zip of all the selected assets
        $zipPath = Path::temp(Str::uuid()->toString().'.zip');
        $zip = new ZipArchive;

        abort_if($zip->open($zipPath, ZipArchive::CREATE) !== true, 500, 'Cannot create zip at '.$zipPath);

        maxPowerCaptain();

        foreach ($assets as $asset) {
            $path = $asset->getVolume()->name.'/'.$asset->getPath();
            $zip->addFromString($path, $asset->getContents());
        }

        $zip->close();

        return response()->download($zipPath, 'assets.zip')->deleteFileAfterSend();
    }

    public function showInFolder(Request $request): Response
    {
        $request->validate([
            'assetId' => ['required'],
        ]);

        $assetId = $request->input('assetId');
        $asset = Asset::findOne($assetId);

        abort_if($asset === null, 400, "Invalid asset ID: $assetId");

        $folder = $asset->getFolder();
        $sourcePath[] = $folder->getSourcePathInfo();

        if ($request->expectsJson()) {
            while (($parent = $folder->getParent()) !== null) {
                $sourcePath[] = $parent->getSourcePathInfo();
                $folder = $parent;
            }

            return new JsonResponse([
                'filename' => $asset->filename,
                'sourcePath' => array_reverse($sourcePath),
            ]);
        }

        $uri = Str::start(UrlHelper::prependCpTrigger($sourcePath[0]['uri']), '/');

        return Uri::of($uri)
            ->withQuery([
                'search' => $asset->filename,
                'includeSubfolders' => '0',
                'sourcePathStep' => "folder:$folder->uid",
            ])
            ->redirect();
    }

    public function moveInfo(Request $request): Response
    {
        $folderIds = $request->input('folderIds', []);
        $assetIds = $request->input('assetIds', []);

        if (! empty($folderIds)) {
            foreach ($folderIds as $folderId) {
                $folder = $this->folders->getFolderById($folderId);
                abort_if(! $folder, 400, "Invalid folder ID: $folderId");
                $descendants = $this->folders->getAllDescendantFolders($folder);
                array_push($folderIds, ...array_keys($descendants));
            }
        }

        $query = DB::table(Table::ASSETS)
            ->whereIn('id', $assetIds)
            ->orWhereIn('folderId', array_unique($folderIds));

        $count = $query->count();
        $totalSize = (int) $query->sum('size');

        return new JsonResponse([
            'count' => $count,
            'totalSize' => $totalSize,
        ]);
    }
}
