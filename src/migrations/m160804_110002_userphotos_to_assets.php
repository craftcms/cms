<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\volumes\Local;
use yii\base\Exception;

/**
 * m160804_110002_userphotos_to_assets migration.
 */
class m160804_110002_userphotos_to_assets extends Migration
{
    /**
     * @var string|null
     */
    private $_basePath;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_basePath = Craft::$app->getPath()->getStoragePath().DIRECTORY_SEPARATOR.'userphotos';

        // Make sure the userphotos folder actually exists
        FileHelper::createDirectory($this->_basePath);

        echo "    > Removing __default__ folder\n";
        FileHelper::removeDirectory($this->_basePath.DIRECTORY_SEPARATOR.'__default__');

        echo "    > Changing the relative path from username/original.ext to original.ext\n";
        $affectedUsers = $this->_moveUserphotos();

        echo "    > Creating a private Volume as default for Users\n";
        $volumeId = $this->_createUserphotoVolume();

        echo "    > Setting the Volume as the default one for userphoto uploads\n";
        $this->_setUserphotoVolume($volumeId);

        echo "    > Converting photos to Assets\n";
        $affectedUsers = $this->_convertPhotosToAssets($volumeId, $affectedUsers);

        echo "    > Updating Users table to drop the photo column and add photoId column.\n";
        $this->dropColumn('{{%users}}', 'photo');
        $this->addColumn('{{%users}}', 'photoId', $this->integer()->after('username')->null());
        $this->addForeignKey(null, '{{%users}}', ['photoId'], '{{%assets}}', ['id'], 'SET NULL', null);

        echo "    > Setting the photoId value\n";
        $this->_setPhotoIdValues($affectedUsers);

        echo "    > Removing all the subfolders.\n";
        $this->_removeSubdirectories();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160804_110002_userphotos_to_assets cannot be reverted.\n";

        return false;
    }

    // Private methods
    // =========================================================================

    /**
     * Move user photos from subfolders to root.
     *
     * @return array
     * @throws Exception in case of failure
     */
    private function _moveUserphotos(): array
    {
        $handle = opendir($this->_basePath);
        if ($handle === false) {
            throw new Exception("Unable to open directory: {$this->_basePath}");
        }

        $affectedUsers = [];

        // Grab the users with photos
        while (($subDir = readdir($handle)) !== false) {
            if ($subDir === '.' || $subDir === '..') {
                continue;
            }
            $path = $this->_basePath.DIRECTORY_SEPARATOR.$subDir;
            if (is_file($path)) {
                continue;
            }

            $user = (new Query())
                ->select(['id', 'photo'])
                ->from(['{{%users}}'])
                ->where(['username' => $subDir])
                ->one($this->db);

            // Make sure the user still exists and has a photo
            if (!$user || empty($user['photo'])) {
                continue;
            }

            // Make sure the original file still exists
            $sourcePath = $this->_basePath.DIRECTORY_SEPARATOR.$subDir.DIRECTORY_SEPARATOR.'original'.DIRECTORY_SEPARATOR.$user['photo'];
            if (!is_file($sourcePath)) {
                continue;
            }

            // Make sure that the filename is unique
            $counter = 0;

            $baseFilename = pathinfo($user['photo'], PATHINFO_FILENAME);
            $extension = pathinfo($user['photo'], PATHINFO_EXTENSION);
            $filename = $baseFilename.'.'.$extension;

            while (is_file($this->_basePath.DIRECTORY_SEPARATOR.$filename)) {
                $filename = $baseFilename.'_'.++$counter.'.'.$extension;
            }

            // In case the filename changed
            $user['photo'] = $filename;

            // Store for reference
            $affectedUsers[] = $user;

            $targetPath = $this->_basePath.DIRECTORY_SEPARATOR.$filename;

            // Move the file to the new location
            rename($sourcePath, $targetPath);
        }

        return $affectedUsers;
    }

    /**
     * Create the user photo volume.
     *
     * @return int volume id
     */
    private function _createUserphotoVolume(): int
    {
        // Safety first!
        $handle = 'userPhotos';
        $name = 'User Photos';

        $counter = 0;

        $existingVolume = (new Query())
            ->select(['id'])
            ->from(['{{%volumes}}'])
            ->where(['handle' => $handle])
            ->one($this->db);

        while ($existingVolume !== null) {
            $handle = 'userPhotos'.++$counter;
            $name = 'User Photos '.$counter;
            $existingVolume = (new Query())
                ->select(['id'])
                ->from(['{{%volumes}}'])
                ->where([
                    'or',
                    ['handle' => $handle],
                    ['name' => $name]
                ])
                ->one($this->db);
        }

        // Set the sort order
        $maxSortOrder = (new Query())
            ->from(['{{%volumes}}'])
            ->max('[[sortOrder]]', $this->db);

        $volumeData = [
            'type' => Local::class,
            'name' => $name,
            'handle' => $handle,
            'hasUrls' => false,
            'url' => null,
            'settings' => Json::encode(['path' => '@storage/userphotos']),
            'fieldLayoutId' => null,
            'sortOrder' => $maxSortOrder + 1
        ];

        $db = Craft::$app->getDb();
        $db->createCommand()
            ->insert('{{%volumes}}', $volumeData)
            ->execute();

        $volumeId = $db->getLastInsertID();

        $folderData = [
            'parentId' => null,
            'volumeId' => $volumeId,
            'name' => $name,
            'path' => null
        ];
        $db->createCommand()
            ->insert('{{%volumefolders}}', $folderData)
            ->execute();

        return $volumeId;
    }

    /**
     * Set the photo volume setting for users.
     *
     * @param int $volumeId
     */
    private function _setUserphotoVolume(int $volumeId)
    {
        $systemSettings = Craft::$app->getSystemSettings();
        $settings = $systemSettings->getSettings('users');
        $settings['photoVolumeId'] = $volumeId;
        $systemSettings->saveSettings('users', $settings);
    }

    /**
     * Convert matching user photos to Assets in a Volume and add that information
     * to the array passed in.
     *
     * @param int $volumeId
     * @param array $userList
     * @return array $userList
     */
    private function _convertPhotosToAssets(int $volumeId, array $userList): array
    {
        $db = Craft::$app->getDb();

        $locales = (new Query())
            ->select(['locale'])
            ->from(['{{%locales}}'])
            ->column($this->db);

        $folderId = (new Query())
            ->select(['id'])
            ->from(['{{%volumefolders}}'])
            ->where([
                'parentId' => null,
                'volumeId' => $volumeId
            ])
            ->scalar($this->db);

        $changes = [];

        foreach ($userList as $user) {
            $filePath = $this->_basePath.DIRECTORY_SEPARATOR.$user['photo'];

            $assetExists = (new Query())
                ->select(['assets.id'])
                ->from(['{{%assets}} assets'])
                ->innerJoin('{{%volumefolders}} volumefolders', '[[volumefolders.id]] = [[assets.folderId]]')
                ->where([
                    'assets.folderId' => $folderId,
                    'filename' => $user['photo']
                ])
                ->exists($this->db);

            if (!$assetExists && is_file($filePath)) {
                $elementData = [
                    'type' => Asset::class,
                    'enabled' => 1,
                    'archived' => 0
                ];
                $db->createCommand()
                    ->insert('{{%elements}}', $elementData)
                    ->execute();

                $elementId = $db->getLastInsertID();

                foreach ($locales as $locale) {
                    $elementI18nData = [
                        'elementId' => $elementId,
                        'locale' => $locale,
                        'slug' => ElementHelper::createSlug($user['photo']),
                        'uri' => null,
                        'enabled' => 1
                    ];
                    $db->createCommand()
                        ->insert('{{%elements_i18n}}', $elementI18nData)
                        ->execute();

                    $contentData = [
                        'elementId' => $elementId,
                        'locale' => $locale,
                        'title' => AssetsHelper::filename2Title(pathinfo($user['photo'], PATHINFO_FILENAME))
                    ];
                    $db->createCommand()
                        ->insert('{{%content}}', $contentData)
                        ->execute();
                }

                $imageSize = Image::imageSize($filePath);
                $assetData = [
                    'id' => $elementId,
                    'volumeId' => $volumeId,
                    'folderId' => $folderId,
                    'filename' => $user['photo'],
                    'kind' => Asset::KIND_IMAGE,
                    'size' => filesize($filePath),
                    'width' => $imageSize[0],
                    'height' => $imageSize[1],
                    'dateModified' => Db::prepareDateForDb(filemtime($filePath))
                ];
                $db->createCommand()
                    ->insert('{{%assets}}', $assetData)
                    ->execute();

                $changes[$user['id']] = $elementId;
            }
        }

        return $changes;
    }

    /**
     * Set photo ID values for the user array passed in.
     *
     * @param array $userlist userId => assetId
     */
    private function _setPhotoIdValues(array $userlist)
    {
        if (is_array($userlist)) {
            $db = Craft::$app->getDb();
            foreach ($userlist as $userId => $assetId) {
                $db->createCommand()
                    ->update('{{%users}}', ['photoId' => $assetId], ['id' => $userId])
                    ->execute();
            }
        }
    }

    /**
     * Remove all the subdirectories in the userphotos folder.
     */
    private function _removeSubdirectories()
    {
        $subDirs = glob($this->_basePath.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);

        foreach ($subDirs as $dir) {
            FileHelper::removeDirectory($dir);
        }
    }
}
