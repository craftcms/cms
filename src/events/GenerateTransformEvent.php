<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Image;

/**
 * Asset generate transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GenerateTransformEvent extends AssetTransformImageEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Image
     */
    public $image;

    /**
     * @var string|null Path to the modified image that should be used instead.
     */
    public $tempPath;
}
