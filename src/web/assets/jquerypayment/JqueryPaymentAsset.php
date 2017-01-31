<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\jquerypayment;

use craft\web\AssetBundle;

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

        $this->js = [
            'jquery.payment'.$this->dotJs(),
        ];

        parent::init();
    }
}
