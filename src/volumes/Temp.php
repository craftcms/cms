<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\volumes;

use Craft;

/**
 * The temporary volume class. Handles the implementation of a temporary volume
 * Craft.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Temp extends Local
{
    /**
     * @inheritdoc
     */
    public $hasUrls = false;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Temp Folder');
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->path !== null) {
            $this->path = rtrim($this->path, '/');
        } else {
            $this->path = Craft::$app->getPath()->getTempAssetUploadsPath();
        }

        if ($this->name === null) {
            $this->name = Craft::t('app', 'Temporary source');
        }

        $this->uid = 'temp-volume';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return null;
    }
}
