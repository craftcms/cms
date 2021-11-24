<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\ImageTransformIndex;

/**
 * Asset transform image event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AssetTransformImageEvent extends AssetEvent
{
    /**
     * @var ImageTransformIndex Asset transform index that is being generated.
     */
    public ImageTransformIndex $transformIndex;
}
