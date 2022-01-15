<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\Asset;

/**
 * AssetPreviewer is the base class for classes that provide asset previewing functionality.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
abstract class AssetPreviewHandler extends Component implements AssetPreviewHandlerInterface
{
    /**
     * @var Asset
     */
    public Asset $asset;

    /**
     * Constructor.
     *
     * @param Asset $asset
     */
    public function __construct(Asset $asset)
    {
        parent::__construct([
            'asset' => $asset,
        ]);
    }
}
