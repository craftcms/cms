<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets;

use Craft;
use yii\web\AssetBundle;

/**
 * JqueryPayment asset bundle.
 */
class JqueryPaymentAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@bower/jquery.payment';

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'query.payment.min.js';
        } else {
            $this->js[] = 'query.payment.js';
        }

        parent::init();
    }
}
