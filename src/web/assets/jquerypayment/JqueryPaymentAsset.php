<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\jquerypayment;

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
        $this->sourcePath = '@bower/jquery.payment/lib';

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'jquery.payment.min.js';
        } else {
            $this->js[] = 'jquery.payment.js';
        }

        parent::init();
    }
}
