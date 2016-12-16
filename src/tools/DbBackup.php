<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tools;

use Craft;
use craft\base\Tool;
use craft\helpers\FileHelper;
use yii\base\ErrorException;
use yii\base\Exception;
use ZipArchive;

/**
 * DbBackup represents a Backup Database tool.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DbBackup extends Tool
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Backup Database');
    }

    /**
     * @inheritdoc
     */
    public static function iconValue()
    {
        return 'database';
    }

    /**
     * @inheritdoc
     */
    public static function optionsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/checkbox',
            [
                'name' => 'downloadBackup',
                'label' => Craft::t('app', 'Download backup?'),
                'checked' => true,
            ]);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function performAction(array $params)
    {
        try {
            $backupPath = Craft::$app->getDb()->backup();
        } catch (\Exception $e) {
            throw new Exception('Could not create backup: '.$e->getMessage());
        }

        if (!is_file($backupPath)) {
            throw new Exception("Could not create backup: the backup file doesn't exist.");
        }

        if (empty($params['downloadBackup'])) {
            return null;
        }

        $zipPath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.pathinfo($backupPath, PATHINFO_FILENAME).'.zip';

        if (is_file($zipPath)) {
            try {
                FileHelper::removeFile($zipPath);
            } catch (ErrorException $e) {
                Craft::warning("Unable to delete the file \"{$zipPath}\": ".$e->getMessage());
            }
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip at '.$zipPath);
        }

        $filename = pathinfo($backupPath, PATHINFO_BASENAME);
        $zip->addFile($backupPath, $filename);
        $zip->close();

        return [
            'backupFile' => pathinfo($filename, PATHINFO_FILENAME)
        ];
    }
}
