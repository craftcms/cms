<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\Element;
use craft\base\Volume;
use craft\db\Table;
use craft\elements\Asset as AssetElement;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GuzzleHttp\Client;
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
    protected $immutableAttributes = ['id', 'uid', 'volumeId', 'folderId'];

    /**
     * Save an asset using the passed arguments.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function saveAsset($source, array $arguments, $context, ResolveInfo $resolveInfo)
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
                'newFolderId' => $newFolderId
            ]);
        }

        /** @var AssetElement $asset */
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

        $asset = $this->populateElementWithData($asset, $arguments);
        $asset = $this->saveElement($asset);

        return $elementService->getElementById($asset->id, AssetElement::class);
    }

    /**
     * Delete an asset identified by the arguments.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return mixed
     * @throws \Throwable if reasons.
     */
    public function deleteAsset($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $assetId = $arguments['id'];

        $elementService = Craft::$app->getElements();
        /** @var AssetElement $asset */
        $asset = $elementService->getElementById($assetId, AssetElement::class);

        if (!$asset) {
            return true;
        }

        $volumeUid = Db::uidById(Table::VOLUMES, $asset->getVolumeId());
        $this->requireSchemaAction('volumes.' . $volumeUid, 'delete');

        $elementService->deleteElementById($assetId);

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function populateElementWithData(Element $asset, array $arguments): Element
    {
        if (!empty($arguments['_file'])) {
            $fileInformation = $arguments['_file'];
            unset($arguments['_file']);
        }

        /** @var AssetElement $asset */
        $asset = parent::populateElementWithData($asset, $arguments);

        if (!empty($fileInformation) && $this->handleUpload($asset, $fileInformation)) {
            if ($asset->id) {
                $asset->setScenario(AssetElement::SCENARIO_REPLACE);
            } else {
                $asset->setScenario(AssetElement::SCENARIO_CREATE);
            }
        }

        return $asset;
    }

    /**
     * Handle file upload.
     *
     * @param AssetElement $asset
     * @param array $fileInformation
     * @return boolean
     * @throws \yii\base\Exception
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
                        } catch (InvalidArgumentException $e) {
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
        } else if (!empty($fileInformation['url'])) {
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
        $asset->filename = $filename;
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
