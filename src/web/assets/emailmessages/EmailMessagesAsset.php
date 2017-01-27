<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\emailmessages;

use craft\web\assets\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for the Email Messages page
 */
class EmailMessagesAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'email_messages.css',
        ];

        $this->js = [
            'email_messages'.$this->dotJs(),
        ];

        parent::init();
    }
}
