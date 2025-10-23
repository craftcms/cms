<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
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
    public function init(): void
    {
        $this->sourcePath = '@assetBundles/jquerypayment/dist';

        $this->js = [
            'jquery.payment.js',
        ];

        parent::init();
    }
}
