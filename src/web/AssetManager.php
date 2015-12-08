<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\helpers\Io;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetManager extends \yii\web\AssetManager
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function publishDirectory($src, $options)
    {
        // See if any of the nested files/folders have a more recent modify date than $src
        $srcModTime = filemtime($src);
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));

        foreach ($objects as $object) {
            /** @var \SplFileInfo $object */
            if (filemtime($object->getPath()) > $srcModTime) {
                Io::touch($src, null, true);
                break;
            }
        }

        return parent::publishDirectory($src, $options);
    }
}
