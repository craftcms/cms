<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.0
 */

namespace craft\base;

use craft\elements\Asset;

/**
 * An AssetPreview provides functionality to preview an arbitrary Asset element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
abstract class AssetPreviewHandler extends Component implements AssetPreviewHandlerInterface
{
    /**
     * @var Asset
     */
    public $asset;

    /**
     * Constructor.
     *
     * @param Asset $asset
     */
    public function __construct(Asset $asset)
    {
        parent::__construct([
            'asset' => $asset
        ]);
    }
}
