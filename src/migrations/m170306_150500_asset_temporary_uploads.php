<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\FileHelper;

/**
 * m170306_150500_asset_temporary_uploads migration.
 */
class m170306_150500_asset_temporary_uploads extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $folderId = Craft::$app->getDb()->quoteColumnName('assets.folderId');
        $volumeFoldersId = Craft::$app->getDb()->quoteColumnName('volumeFolders.id');


        echo "    > Fetching indexed temporary uploads \n";
        // Get indexed temporary uploads
        $assets = (new Query())
            ->select(['assets.id', 'assets.filename', 'assets.folderId', 'volumeFolders.path'])
            ->from('{{%assets}} assets')
            ->innerJoin('{{%volumefolders}} volumeFolders', $folderId . ' = ' . $volumeFoldersId)
            ->where(['assets.volumeId' => null])
            ->all($this->db);

        $folderCache = [];
        $previousFolderList = [];

        echo "    > Moving temporary uploads \n";
        // For every file
        foreach ($assets as $asset) {
            $pathParts = explode('/', $asset['path']);
            $topFolderPath = $pathParts[0];

            // Find the folder ID to move this to.
            if (empty($folderCache[$topFolderPath])) {
                $folderCache[$topFolderPath] = (new Query())->select('id')
                    ->from('{{%volumefolders}}')
                    ->where(['volumeId' => null])
                    ->andWhere(['path' => $topFolderPath . '/'])
                    ->scalar($this->db);
            }

            $topFolderId = $folderCache[$topFolderPath];

            // A reasonable precaution
            if ($topFolderId !== $asset['folderId']) {
                $previousFolderList[$asset['folderId']] = $asset['path'];
            }

            $basePath = Craft::$app->getPath()->getAssetsPath() . DIRECTORY_SEPARATOR . 'tempuploads' . DIRECTORY_SEPARATOR;
            $from = $basePath . str_replace('/', DIRECTORY_SEPARATOR, $asset['path']) . $asset['filename'];
            $to = $basePath . $topFolderPath . DIRECTORY_SEPARATOR . $asset['filename'];

            // Track what needs to be changed
            $updatedProperties = [
                'folderId' => $topFolderId
            ];

            // If the file doesn't even exist, delete the record of it.
            if (!file_exists($from)) {
                $this->delete('{{%elements}}', ['id' => $asset['id']]);
                continue;
            }

            if (file_exists($to)) {
                $extension = pathinfo($asset['filename'], PATHINFO_EXTENSION);
                $filename = pathinfo($asset['filename'], PATHINFO_FILENAME);

                $increment = 0;

                // Create a new filename to dodge conflicts. If this fails for a while, start
                // naming the files in random names.
                do {
                    $increment++;

                    if ($increment < 50) {
                        $newFilename = $filename . '_' . $increment . '.' . $extension;
                    } else {
                        $newFilename = uniqid('assets', false) . '.' . $extension;
                    }
                } while (file_exists($basePath . $topFolderPath . DIRECTORY_SEPARATOR . $newFilename));

                $updatedProperties['filename'] = $newFilename;
                $to = $basePath . $topFolderPath . DIRECTORY_SEPARATOR . $newFilename;
            }

            // Copy instead of move here to be extra safe. Copies left behind on no errors will be removed a few lines down anwyay.
            copy($from, $to);

            // Change properties
            $this->update('{{%assets}}', $updatedProperties, ['id' => $asset['id']]);
        }

        echo "    > Deleting obsolete folders \n";

        // Delete all the old volume folders
        $this->delete('{{%volumefolders}}', ['id' => array_keys($previousFolderList)]);

        // And directories
        $basePath = Craft::$app->getPath()->getAssetsPath() . DIRECTORY_SEPARATOR . 'tempuploads' . DIRECTORY_SEPARATOR;

        foreach ($previousFolderList as $folderPath) {
            FileHelper::removeDirectory($basePath . $folderPath);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170306_150500_asset_temporary_uploads cannot be reverted.\n";

        return false;
    }
}
