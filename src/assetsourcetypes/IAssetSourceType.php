<?php
namespace craft\app\assetsourcetypes;

use craft\app\filesourcetypes\IFileSourceType;

/**
 * Interface IAssetSourceType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.assetsourcetypes
 * @since     3.0
 */
interface IAssetSourceType extends IFileSourceType
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether this source stores files locally on the server.
     *
     * @return bool Whether files are stored locally.
     */
    public function isLocal();

    /**
     * Returns the URL to the source, if it’s accessible via HTTP traffic.
     *
     * @return string|false The root URL, or `false` if there isn’t one.
     */
    public function getRootUrl();

}
