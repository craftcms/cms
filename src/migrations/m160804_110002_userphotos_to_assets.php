<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\ElementHelper;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\helpers\StringHelper;

/**
 * m160804_110002_userphotos_to_assets migration.
 */
class m160804_110002_userphotos_to_assets extends Migration
{
    /**
     * @var string
     */
    private $_basePath;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_basePath = Craft::$app->getPath()->getStoragePath().'/'.'userphotos';
        Craft::info('Removing __default__ folder');
        Io::deleteFolder($this->_basePath.'/__default__');

        Craft::info('Changing the relative path from username/original.ext to original.ext');
        $affectedUsers = $this->_moveUserphotos();

        Craft::info('Creating a private Yii Volume as default for Users');
        $volumeId = $this->_createUserphotoVolume();

        Craft::info('Setting the Volume as the default one for userphoto uploads');
        $this->_setUserphotoVolume($volumeId);

        Craft::info('Converting photos to Assets');
        $affectedUsers = $this->_convertPhotosToAssets($volumeId, $affectedUsers);

        Craft::info('Updating Users table to drop the photo column and add photoId column.');
        $this->dropColumn('{{%users}}', 'photo');
        $this->addColumnAfter('{{%users}}', 'photoId', $this->integer()->null(), 'username');
        $this->addForeignKey($this->db->getForeignKeyName('{{%users}}', 'photoId'), '{{%users}}', 'photoId', '{{%assets}}', 'id', 'SET NULL', null);

        Craft::info('Setting the photoId value');
        $this->_setPhotoIdValues($affectedUsers);

        Craft::info('Removing all the subfolders.');
        $this->_removeSubfolders();

        Craft::info('All done');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo 'm160804_110002_userphotos_to_assets cannot be reverted.\n';
        return false;
    }

    // Private methods
    // =========================================================================

    /**
     * Move user photos from subfolders to root.
     *
     * @return array
     */
    private function _moveUserphotos()
    {
        $affectedUsers = [];
        $subfolders = Io::getFolderContents($this->_basePath, false);

        if ($subfolders) {
            // Grab the users with photos
            foreach ($subfolders as $subfolder) {
                $usernameOrEmail = trim(StringHelper::replace($subfolder, $this->_basePath, ''), '/');

                $user = (new Query())
                    ->select('id, photo')
                    ->from('{{%users}}')
                    ->where('username = :username', [':username' => $usernameOrEmail])
                    ->one();

                $sourcePath = $subfolder.'original/'.$user['photo'];

                // If the file actually exists
                if (Io::fileExists($sourcePath)) {
                    // Make sure that the filename is unique
                    $counter = 0;

                    $baseFilename = Io::getFilename($user['photo'], false);
                    $extension = Io::getExtension($user['photo']);
                    $filename = $baseFilename.'.'.$extension;

                    while (Io::fileExists($this->_basePath.'/'.$filename)) {
                        $filename = $baseFilename.'_'.++$counter.'.'.$extension;
                    }

                    // In case the filename changed
                    $user['photo'] = $filename;

                    // Store for reference
                    $affectedUsers[] = $user;

                    $targetPath = $this->_basePath.'/'.$filename;

                    // Move the file to the new location
                    Io::move($sourcePath, $targetPath);
                }
            }
        }

        return $affectedUsers;
    }

    /**
     * Create the user photo volume.
     *
     * @return integer volume id
     */
    private function _createUserphotoVolume()
    {
        // Safety first!
        $handle = 'userPhotos';
        $name = 'User Photos';

        $counter = 0;
        $existingVolume = (new Query())
            ->select('id')
            ->from('{{%volumes}}')
            ->where('handle = :handle', [':handle' => $handle])
            ->one();

        while (!empty($existingVolume)) {
            $handle = 'userPhotos'.++$counter;
            $name = 'User Photos '.$counter;
            $existingVolume = (new Query())
                ->select('id')
                ->from('{{%volumes}}')
                ->where('handle = :handle', [':handle' => $handle])
                ->orWhere('name = :name', [':name' => $name])
                ->one();
        }

        // Set the sort order
        $maxSortOrder = (new Query())
            ->select('max(sortOrder)')
            ->from('{{%volumes}}')
            ->scalar();

        $volumeData = [
            'type' => 'craft\app\volumes\Local',
            'name' => $name,
            'handle' => $handle,
            'hasUrls' => null,
            'url' => null,
            'settings' => Json::encode(['path' => $this->_basePath]),
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
     * @param integer $volumeId
     *
     * @return void
     */
    private function _setUserphotoVolume($volumeId)
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
     * @param integer $volumeId
     * @param array $userList
     *
     * @return array $userList
     */
    private function _convertPhotosToAssets($volumeId, $userList)
    {
        $db = Craft::$app->getDb();

        $locales = (new Query())
            ->select('locale')
            ->from('{{%locales}}')
            ->column();

        $folderId = (new Query())
            ->select('id')
            ->from('{{%volumefolders}}')
            ->where('parentId is null')
            ->andWhere('volumeId = :volumeId', [':volumeId' => $volumeId])
            ->scalar();

        $changes = [];
        foreach ($userList as $user) {
            $filePath = $this->_basePath.'/'.$user['photo'];

            $assetExists = (new Query())
                ->select('assets.id')
                ->from('{{%assets}} assets')
                ->innerJoin('{{%volumefolders}} volumefolders', 'volumefolders.id = assets.folderId')
                ->where('assets.folderId = :folderId', [':folderId' => $folderId])
                ->andWhere('filename = :filename', [':filename' => $user['photo']])
                ->one();

            if (!$assetExists && Io::fileExists($filePath)) {
                $elementData = [
                    'type' => 'craft\app\elements\Asset',
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
                        'title' => StringHelper::toTitleCase(Io::getFilename($user['photo'], false))
                    ];
                    $db->createCommand()
                        ->insert('{{%content}}', $contentData)
                        ->execute();
                }

                $imageSize = Image::getImageSize($filePath);
                $assetData = [
                    'id' => $elementId,
                    'volumeId' => $volumeId,
                    'folderId' => $folderId,
                    'filename' => $user['photo'],
                    'kind' => 'image',
                    'size' => Io::getFileSize($filePath),
                    'width' => $imageSize[0],
                    'height' => $imageSize[1],
                    'dateModified' => Io::getLastTimeModified($filePath)
                ];
                $db->createCommand()
                    ->insert('{{%assets}}', $assetData)
                    ->execute();

                $changes[$user['id']] = $db->getLastInsertID();
            }
        }

        return $changes;
    }

    /**
     * Set photo ID values for the user array passed in.
     *
     * @param array $userlist userId => assetId
     *
     * @return void
     */
    private function _setPhotoIdValues($userlist)
    {
        if (is_array($userlist)) {
            $db = Craft::$app->getDb();
            foreach ($userlist as $userId => $assetId) {
                $db->createCommand()
                    ->update('{{%users}}', ['photoId' => $assetId], 'id = :userId', [':userId' => $userId])
                    ->execute();
            }
        }
    }

    /**
     * Remove all the subfolders in the userphoto folder.
     */
    private function _removeSubfolders()
    {
        $folders = Io::getFolders($this->_basePath.'/');

        if (is_array($folders)) {
            foreach ($folders as $folder) {
                Io::deleteFolder($folder);
            }
        }
    }
}
