<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\inputmask;

use yii\widgets\MaskedInputAsset;

/**
 * Input Mask asset bundle.
 *
 * @since 3.7.x.
 */
class InputMaskAsset extends MaskedInputAsset
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';
}