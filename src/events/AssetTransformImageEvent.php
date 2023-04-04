<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\AssetTransformIndex;

/**
 * Asset transform image event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetTransformImageEvent extends AssetEvent
{
    /**
     * @var AssetTransformIndex The asset transform index that is being generated.
     */
    public $transformIndex;
}
