<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * Replace asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ReplaceAssetEvent extends AssetEvent
{
    /**
     * @var string|null file on server that is being used to replace
     */
    public $replaceWith;

    /**
     * @var string|null the file name that will be used
     */
    public $filename;
}
