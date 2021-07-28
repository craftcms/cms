<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\authentication\chain;

use craft\validators\UserPasswordValidator;
use craft\web\assets\cp\CpAsset;
use craft\web\View;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Auth chain
 */
class ChainAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'AuthenticationChainHandler.min.js',
        'AuthenticationStep.min.js',
        'VerificationCode.min.js',
    ];
}
