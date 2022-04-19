<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Asset as AssetElement;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\models\Volume;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GuzzleHttp\Client;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * Class Asset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Asset extends ElementMutationResolver
{
    /** @inheritdoc */
    protected array $immutableAttributes = ['id', 'uid', 'volumeId', 'folderId'];

    /**
     * Save an asset using the passed arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return AssetElement
     * @throws Throwable if reasons.
     */
    public function saveAsset(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): AssetElement
    {
        /** @var Volume $volume */
        $volume = $this->getResolutionData('volume');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

        $newFolderId = $arguments['newFolderId'] ?? null;
        $assetService = Craft::$app->getAssets();

        if ($canIdentify) {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'save');

            if (!empty($arguments['uid'])) {
                $asset = $elementService->createElementQuery(AssetElement::class)->uid($arguments['uid'])->one();
            } else {
                $asset = $elementService->getElementById($arguments['id'], AssetElement::class);
            }

            if (!$asset) {
                throw new Error('No such asset exists');
            }
        } else {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'create');

            if (empty($arguments['_file'])) {
                throw new UserError('Impossible to create an asset without providing a file');
            }

            if (empty($newFolderId)) {
                $newFolderId = $assetService->getRootFolderByVolumeId($volume->id)->id;
            }

            $asset = $elementService->createElement([
                'type' => AssetElement::class,
                'volumeId' => $volume->id,
                'newFolderId' => $newFolderId,
            ]);
        }

        if (empty($newFolderId)) {
            if (!$canIdentify) {
                $asset->newFolderId = $assetService->getRootFolderByVolumeId($volume->id)->id;
            }
        } else {
            $folder = $assetService->getFolderById($newFolderId);

            if (!$folder || $folder->volumeId != $volume->id) {
                throw new UserError('Invalid folder id provided');
            }
        }

        $asset->setVolumeId($volume->id);

        $asset = $this->populateElementWithData($asset, $arguments, $resolveInfo);
        $asset = $this->saveElement($asset);

        return $elementService->getElementById($asset->id, AssetElement::class);
    }

    /**
     * Delete an asset identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @throws Throwable if reasons.
     */
    public function deleteAsset(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): void
    {
        $assetId = $arguments['id'];

        $elementService = Craft::$app->getElements();
        /** @var AssetElement|null $asset */
        $asset = $elementService->getElementById($assetId, AssetElement::class);

        if (!$asset) {
            return;
        }

        $volumeUid = Db::uidById(Table::VOLUMES, $asset->getVolumeId());
        $this->requireSchemaAction('volumes.' . $volumeUid, 'delete');

        $elementService->deleteElementById($assetId);
    }

    /**
     * @inheritdoc
     */
    protected function populateElementWithData(ElementInterface $element, array $arguments, ?ResolveInfo $resolveInfo = null): ElementInterface
    {
        if (!empty($arguments['_file'])) {
            $fileInformation = $arguments['_file'];
            unset($arguments['_file']);
        }

        /** @var AssetElement $element */
        $element = parent::populateElementWithData($element, $arguments, $resolveInfo);

        if (!empty($fileInformation) && $this->handleUpload($element, $fileInformation)) {
            if ($element->id) {
                $element->setScenario(AssetElement::SCENARIO_REPLACE);
            } else {
                $element->setScenario(AssetElement::SCENARIO_CREATE);
            }
        }

        return $element;
    }

    /**
     * Handle file upload.
     *
     * @param AssetElement $asset
     * @param array $fileInformation
     * @return bool
     * @throws Exception
     */
    protected function handleUpload(AssetElement $asset, array $fileInformation): bool
    {
        $tempPath = null;
        $filename = null;

        if (!empty($fileInformation['fileData'])) {
            $dataString = $fileInformation['fileData'];
            $fileData = null;

            if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9\+\.\-]+);)?base64,(?<data>.+)/i', $dataString, $matches)) {
                // Decode the file
                $fileData = base64_decode($matches['data']);
            }

            if ($fileData) {
                if (empty($fileInformation['filename'])) {
                    // Make up a filename
                    $extension = null;
                    if (isset($matches['type'])) {
                        try {
                            $extension = FileHelper::getExtensionByMimeType($matches['type']);
                        } catch (InvalidArgumentException) {
                        }
                    }
                    if (!$extension) {
                        throw new UserError('Invalid file data provided.');
                    }
                    $filename = 'Upload.' . $extension;
                } else {
                    $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                }

                $tempPath = AssetsHelper::tempFilePath($extension);
                file_put_contents($tempPath, $fileData);
            } else {
                throw new UserError('Invalid file data provided');
            }
        } elseif (!empty($fileInformation['url'])) {
            $url = $fileInformation['url'];

            if (empty($fileInformation['filename'])) {
                $filename = AssetsHelper::prepareAssetName(pathinfo(UrlHelper::stripQueryString($url), PATHINFO_BASENAME));
            } else {
                $filename = AssetsHelper::prepareAssetName($fileInformation['filename']);
            }

            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            // Download the file
            $tempPath = AssetsHelper::tempFilePath($extension);
            $this->createGuzzleClient()->request('GET', $url, ['sink' => $tempPath]);
        }

        if (!$tempPath || !$filename) {
            return false;
        }

        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->avoidFilenameConflicts = true;

        return true;
    }

    /**
     * Create the guzzle client.
     *
     * @return Client
     */
    protected function createGuzzleClient(): Client
    {
        return Craft::createGuzzleClient();
    }
}
