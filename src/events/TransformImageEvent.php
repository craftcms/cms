<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\ImageTransformIndex;

/**
 * Transform image event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TransformImageEvent extends AssetEvent
{
    /**
     * @var ImageTransformIndex Image transform index that is being generated.
     */
    public ImageTransformIndex $transformIndex;
}
