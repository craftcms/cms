<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\tools;

use Craft;
use craft\app\base\Tool;
use craft\app\helpers\Io;
use craft\app\io\Zip;

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
     */
    public function performAction($params = [])
    {
        // In addition to the default tables we want to ignore data in, we also don't care about data in the session
        // table in this tools' case.
        $file = Craft::$app->getDb()->backup();

        if (Io::fileExists($file) && isset($params['downloadBackup']) && (bool)$params['downloadBackup']) {
            $destZip = Craft::$app->getPath()->getTempPath().'/'.Io::getFilename($file,
                    false).'.zip';

            if (Io::fileExists($destZip)) {
                Io::deleteFile($destZip, true);
            }

            Io::createFile($destZip);

            if (Zip::add($destZip, $file, Craft::$app->getPath()->getDbBackupPath())) {
                return ['backupFile' => Io::getFilename($destZip, false)];
            }
        }

        return null;
    }
}
