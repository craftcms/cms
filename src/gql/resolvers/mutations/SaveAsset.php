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
use craft\elements\Asset;
use craft\gql\base\MutationResolver;
use craft\helpers\Assets;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class SaveAsset
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveAsset extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var Volume $volume */
        $volume = $this->_getData('volume');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);

        if ($canIdentify) {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'save');
            if (!empty($arguments['uid'])) {
                $asset = Asset::findOne(['uid' => $arguments['uid']]);
            } else {
                $asset = Asset::findOne($arguments['id']);
            }
        } else {
            $this->requireSchemaAction('volumes.' . $volume->uid, 'create');

            if (empty($arguments['_file'])) {
                throw new UserError('Impossible to create an asset without providing a file');
            }

            if (empty($arguments['newFolderId'])) {
                throw new UserError('Impossible to create an asset without providing a folder');
            }

            $asset = new Asset(['volumeId' => $volume->id, 'newFolderId' => $arguments['newFolderId']]);
        }


        if (!empty($arguments['newFolderId'])) {
            $folder = Craft::$app->getAssets()->getFolderById($arguments['newFolderId']);

            if (!$folder || $folder->volumeId != $volume->id) {
                throw new UserError('Invalid folder id provided');
            }
        } else {
            if ($asset->volumeId != $volume->id) {
                throw new UserError('A folder id must be provided to change the asset\'s volume.');
            }
        }

        // Implement file operations.

        $asset = $this->populateElementWithData($asset, $arguments);

        $this->saveElement($asset);

        return Asset::find()->anyStatus()->id($asset->id)->one();
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

        /** @var Asset $asset */
        $asset = parent::populateElementWithData($asset, $arguments);

        if (!empty($fileInformation) && $this->_handleUpload($asset, $fileInformation)) {
            if ($asset->id) {
                $asset->setScenario(Asset::SCENARIO_REPLACE);
            } else {
                $asset->setScenario(Asset::SCENARIO_CREATE);
            }
        }

        return $asset;
    }

    /**
     * Handle file upload.
     *
     * @param Asset $asset
     * @param $fileInformation
     * @return boolean
     * @throws \yii\base\Exception
     */
    private function _handleUpload(Asset $asset, $fileInformation): bool
    {
        $tempPath = null;
        $filename = null;

        if (!empty($fileInformation['fileData'])) {
            if (empty($fileInformation['filename'])) {
                throw new UserError('Missing file name');
            }

            $dataString = $fileInformation['fileData'];
            $filename = Assets::prepareAssetName($fileInformation['filename']);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $fileData = null;

            if (preg_match('/^data:(?<type>[a-z0-9]+\/[a-z0-9\+]+);base64,(?<data>.+)/i', $dataString, $matches)) {
                // Decode the file
                $fileData = base64_decode($matches['data']);
            }

            if ($fileData) {
                $tempPath = Assets::tempFilePath($extension);
                file_put_contents($tempPath, $fileData);
            } else {
                throw new UserError('Invalid file data provided');
            }
        } else if (!empty($fileInformation['url'])) {
            $url = $fileInformation['url'];
            $filename = Assets::prepareAssetName(pathinfo($url, PATHINFO_BASENAME));
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            // Download the file
            $tempPath = Assets::tempFilePath($extension);
            Craft::createGuzzleClient()->request('GET', $url, ['sink' => $tempPath]);
        }

        if (!$tempPath || !$filename) {
            return false;
        }

        $asset->tempFilePath = $tempPath;
        $asset->newFilename = $filename;
        $asset->avoidFilenameConflicts = true;

        return true;
    }
}
