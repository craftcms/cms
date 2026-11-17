<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\uploads;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\fields\Assets as AssetsField;
use craft\helpers\Assets;
use craft\helpers\ConfigHelper;
use craft\helpers\Db;
use craft\helpers\Image;
use craft\i18n\Formatter;
use craft\models\VolumeFolder;
use DateTime;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Uploads files to asset folders, or replaces assets’ files.
 *
 * Parameters, as in Craft 6:
 *
 * - `folderId`, or `fieldId` with an optional `elementId` and `siteId`: the destination, resolved the way the control
 *   panel resolves it
 * - `operation`: `upload` (the default) or `replace`
 * - `assetId`: the asset to replace
 * - `context`: anything the client wants passed to [[\craft\services\Uploads::EVENT_AUTHORIZE_UPLOAD]] handlers
 *
 * Guests can only upload, and only to a folder or an Assets field without an element. Their uploads need an
 * [[\craft\services\Uploads::EVENT_AUTHORIZE_UPLOAD]] handler’s approval, unless they go to the guest’s own temporary
 * upload folder.
 *
 * Files are moved into place within their volume’s filesystem, so they aren’t downloaded, unless Craft would clean
 * them (see <config5:sanitizeCpImageUploads>), the field has a selection condition, or they replace an asset’s file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class AssetUploadHandler implements UploadHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function authorize(array $params, ?string $filename, ?int $size, ?User $user): array
    {
        $this->assertFile($filename, $size);
        $operation = $params['operation'] ?? 'upload';
        $context = $params['context'] ?? [];

        if (!is_array($context)) {
            throw new BadRequestHttpException('The context must be an object.');
        }

        if ($operation === 'replace') {
            if (!$user) {
                throw new ForbiddenHttpException('Guests can’t replace files.');
            }

            $asset = $this->asset($params);
            $this->requireFolderPermission($user, 'replaceFiles', $asset->getFolder());

            if ($asset->getVolumeId() && $asset->uploaderId != $user->id) {
                $this->requireFolderPermission($user, 'replacePeerFiles', $asset->getFolder());
            }

            return ['operation' => 'replace', 'assetId' => $asset->id, 'context' => $context];
        }

        if ($operation !== 'upload') {
            throw new BadRequestHttpException('The operation must be upload or replace.');
        }

        if (!$user && isset($params['elementId'])) {
            throw new ForbiddenHttpException('Guests can’t upload to an element’s Assets field.');
        }

        [$folder] = $this->destination($params);

        if (!$folder->volumeId) {
            // Only the uploader’s own temporary folder is allowed, whoever they are
            if ($folder->id !== Craft::$app->getAssets()->getUserTemporaryUploadFolder($user)->id) {
                throw new ForbiddenHttpException('You are not allowed to upload to this folder.');
            }
        } elseif ($user) {
            $this->requireFolderPermission($user, 'saveAssets', $folder);
        }

        // The resolved folder is always included, so a field that resolves to a different folder by the time the upload
        // is completed (and so possibly to a different filesystem than the file is staged on) fails reauthorization
        return array_filter([
            'operation' => 'upload',
            'folderId' => $folder->id,
            'fieldId' => isset($params['fieldId']) ? (int)$params['fieldId'] : null,
            'elementId' => isset($params['elementId']) ? (int)$params['elementId'] : null,
            'siteId' => isset($params['siteId']) ? (int)$params['siteId'] : null,
            'context' => $context,
        ], fn($value) => $value !== null);
    }

    /**
     * @inheritdoc
     */
    public function stagingVolumeUid(array $params): ?string
    {
        $folder = ($params['operation'] ?? 'upload') === 'replace'
            ? $this->asset($params)->getFolder()
            : $this->destination($params)[0];

        return $folder->volumeId ? $folder->getVolume()->uid : null;
    }

    /**
     * @inheritdoc
     */
    public function complete(array $params, StagedUpload $upload): array
    {
        if ($params['operation'] === 'replace') {
            return $this->replace($this->asset($params), $upload);
        }

        [$folder, $field, $element] = $this->destination($params);
        $selectionCondition = $field?->getSelectionCondition();

        if ($selectionCondition instanceof ElementCondition) {
            $selectionCondition->referenceElement = $element;
        }

        if ($selectionCondition || $this->shouldClean($upload->filename)) {
            return $this->uploadLocally($upload, $folder, $selectionCondition, $params['siteId'] ?? null);
        }

        return $this->uploadInPlace($upload, $folder, $params['siteId'] ?? null);
    }

    /**
     * Creates an asset around the uploaded file, after moving it into the folder within its filesystem.
     */
    private function uploadInPlace(StagedUpload $upload, VolumeFolder $folder, ?int $siteId): array
    {
        $filename = Assets::prepareAssetName($upload->filename);
        $suggestedFilename = Craft::$app->getAssets()->getNameReplacementInFolder($filename, $folder->id);
        $volume = $folder->getVolume();
        $path = $volume->getSubpath() . $folder->path . $suggestedFilename;

        $upload->fs->renameFile($upload->path, $path);

        $asset = new Asset();
        $asset->setVolumeId($folder->volumeId);
        $asset->folderId = $folder->id;
        $asset->folderPath = $folder->path;
        $asset->setFilename($suggestedFilename);
        $asset->kind = Assets::getFileKindByExtension($suggestedFilename);
        $asset->size = $upload->size;
        $asset->dateModified = new DateTime();
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->title = Assets::filename2Title(pathinfo($filename, PATHINFO_FILENAME));
        $this->setSite($asset, $siteId);

        if ($asset->kind === Asset::KIND_IMAGE) {
            // Read only as much of the file as the dimensions need, rather than downloading it
            $stream = $upload->fs->getFileStream($path);
            try {
                $dimensions = Image::imageSizeByStream($stream);
                if ($dimensions) {
                    [$asset->width, $asset->height] = $dimensions;
                }
            } catch (Throwable) {
            } finally {
                fclose($stream);
            }
        }

        // The file is already in place, so save the asset the way indexing does
        $asset->setScenario(Asset::SCENARIO_INDEX);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            $upload->fs->deleteFile($path);
            throw new BadRequestHttpException(implode("\n", $asset->getErrorSummary(true)) ?: 'The asset could not be saved.');
        }

        if ($suggestedFilename !== $filename) {
            $asset->conflictingFilename = $filename;
            $asset->suggestedFilename = $suggestedFilename;
        }

        return $this->result($asset, $folder);
    }

    /**
     * Saves the file the way Craft’s `assets/upload` action does, from a local copy.
     */
    private function uploadLocally(StagedUpload $upload, VolumeFolder $folder, ?ElementConditionInterface $selectionCondition, ?int $siteId): array
    {
        $elements = Craft::$app->getElements();
        $filename = Assets::prepareAssetName($upload->filename);
        $tempPath = $this->localCopy($upload);
        $originalFolder = $originalFilename = null;

        if ($selectionCondition) {
            $tempFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

            if ($folder->id !== $tempFolder->id) {
                // Upload to the user’s temporary folder with a temporary name, until it’s known to be selectable
                $originalFolder = $folder;
                $originalFilename = $filename;
                $folder = $tempFolder;
                $filename = uniqid('asset', true) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            }
        }

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->newFolderId = $folder->id;
        $asset->setVolumeId($folder->volumeId);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;
        $this->setSite($asset, $siteId);

        if ($originalFilename !== null) {
            $asset->title = Assets::filename2Title(pathinfo($originalFilename, PATHINFO_FILENAME));
        }

        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!$elements->saveElement($asset)) {
            throw new BadRequestHttpException(implode("\n", $asset->getErrorSummary(true)) ?: 'The asset could not be saved.');
        }

        if ($selectionCondition) {
            if (!$selectionCondition->matchElement($asset)) {
                $elements->deleteElement($asset, true);
                throw new BadRequestHttpException(Craft::t('app', '{filename} isn’t selectable for this field.', [
                    'filename' => $upload->filename,
                ]));
            }

            if ($originalFolder !== null) {
                $asset->newFilename = $originalFilename;
                $asset->newFolderId = $originalFolder->id;
                $asset->setScenario(Asset::SCENARIO_MOVE);

                if (!$elements->saveElement($asset)) {
                    throw new BadRequestHttpException(implode("\n", $asset->getErrorSummary(true)) ?: 'The asset could not be moved.');
                }

                $folder = $originalFolder;
            }
        }

        return $this->result($asset, $folder);
    }

    private function replace(Asset $asset, StagedUpload $upload): array
    {
        $filename = Assets::prepareAssetName($upload->filename);
        Craft::$app->getAssets()->replaceAssetFile($asset, $this->localCopy($upload), $filename);

        // The same data as the assets/replace-file action
        return [
            'assetId' => $asset->id,
            'filename' => $asset->getFilename(),
            'formattedSize' => $asset->getFormattedSize(0),
            'formattedSizeInBytes' => $asset->getFormattedSizeInBytes(false),
            'formattedDateUpdated' => Craft::$app->getFormatter()->asDatetime($asset->dateUpdated, Formatter::FORMAT_WIDTH_SHORT, true),
            'dimensions' => $asset->getDimensions(),
            'updatedTimestamp' => $asset->dateUpdated->getTimestamp(),
            'resultingUrl' => $asset->getUrl(),
        ];
    }

    /**
     * Returns the same data as the `assets/upload` action.
     */
    private function result(Asset $asset, VolumeFolder $folder): array
    {
        $url = null;

        try {
            $url = $asset->getUrl();
        } catch (Throwable) {
        }

        if ($asset->conflictingFilename === null) {
            return [
                'filename' => $asset->getFilename(),
                'assetId' => $asset->id,
                'url' => $url,
            ];
        }

        $conflictingAsset = Asset::find()
            ->folderId($folder->id)
            ->filename(Db::escapeParam($asset->conflictingFilename))
            ->site('*')
            ->preferSites([Craft::$app->getSites()->getCurrentSite()->id])
            ->unique()
            ->one();

        return [
            'conflict' => Craft::t('app', 'A file with the name “{filename}” already exists.', ['filename' => $asset->conflictingFilename]),
            'assetId' => $asset->id,
            'filename' => $asset->conflictingFilename,
            'conflictingAssetId' => $conflictingAsset->id ?? null,
            'suggestedFilename' => $asset->suggestedFilename,
            'conflictingAssetUrl' => ($conflictingAsset && $conflictingAsset->getVolume()->getFs()->hasUrls) ? $conflictingAsset->getUrl() : null,
            'url' => $url,
        ];
    }

    /**
     * Whether Craft would clean the file when saving it, which needs a local copy.
     */
    private function shouldClean(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Same condition as Asset::afterSave(), which also covers SVG sanitizing
        return (Image::canManipulateAsImage($extension) || $extension === 'svg') && (
            !Craft::$app->getRequest()->getIsCpRequest() ||
            Craft::$app->getConfig()->getGeneral()->sanitizeCpImageUploads
        );
    }

    private function localCopy(StagedUpload $upload): string
    {
        $tempPath = Assets::tempFilePath(pathinfo($upload->filename, PATHINFO_EXTENSION) ?: 'tmp');
        $upload->copyTo($tempPath);
        return $tempPath;
    }

    /**
     * @return array{VolumeFolder, AssetsField|null, ElementInterface|null}
     */
    private function destination(array $params): array
    {
        $field = $element = null;

        if (isset($params['fieldId'])) {
            $field = Craft::$app->getFields()->getFieldById((int)$params['fieldId']);

            if (!$field instanceof AssetsField) {
                throw new BadRequestHttpException('The field provided is not an Assets field.');
            }

            if (isset($params['elementId'])) {
                $element = Craft::$app->getElements()->getElementById((int)$params['elementId'], null, $params['siteId'] ?? null);
            }

            $folderId = $field->resolveDynamicPathToFolderId($element);
        } elseif (isset($params['folderId'])) {
            $folderId = (int)$params['folderId'];
        } else {
            throw new BadRequestHttpException('No target destination provided for uploading.');
        }

        $folder = Craft::$app->getAssets()->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The target folder provided for uploading is not valid.');
        }

        return [$folder, $field, $element];
    }

    private function asset(array $params): Asset
    {
        $asset = isset($params['assetId']) ? Craft::$app->getAssets()->getAssetById((int)$params['assetId']) : null;

        if (!$asset) {
            throw new NotFoundHttpException('Asset not found.');
        }

        return $asset;
    }

    private function requireFolderPermission(User $user, string $permission, VolumeFolder $folder): void
    {
        if (!$folder->volumeId) {
            if ($folder->id === Craft::$app->getAssets()->getUserTemporaryUploadFolder($user)->id) {
                return;
            }

            throw new ForbiddenHttpException('You are not allowed to upload to this folder.');
        }

        if (!$user->can("$permission:{$folder->getVolume()->uid}")) {
            throw new ForbiddenHttpException('You are not allowed to upload to this folder.');
        }
    }

    private function setSite(Asset $asset, ?int $siteId): void
    {
        // Save the asset in the site it was uploaded from
        if ($siteId && in_array($siteId, Craft::$app->getSites()->getEditableSiteIds(), true)) {
            $asset->siteId = $siteId;
        }
    }

    private function assertFile(?string $filename, ?int $size): void
    {
        if ($filename === null || trim($filename) === '' || strlen($filename) > 255
            || str_contains($filename, '/') || str_contains($filename, '\\') || preg_match('/[\x00-\x1f\x7f]/', $filename)) {
            throw new BadRequestHttpException('Provide a valid filename.');
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, Craft::$app->getConfig()->getGeneral()->allowedFileExtensions, true)) {
            throw new BadRequestHttpException(Craft::t('app', '“{extension}” is not an allowed file extension.', ['extension' => $extension]));
        }

        if ($size === null || $size < 1) {
            throw new BadRequestHttpException('Provide the file’s size.');
        }

        if ($size > ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize)) {
            throw new BadRequestHttpException(Craft::t('app', '“{filename}” is too large.', ['filename' => $filename]));
        }
    }
}
