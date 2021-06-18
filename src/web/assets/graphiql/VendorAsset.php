<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\graphiql;

use craft\web\AssetBundle;

/**
 * VendorAsset asset bundle.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 * @deprecated in 3.3.16. Use [[GraphiqlAsset]] instead.
 */
class VendorAsset extends AssetBundle
{
    /** @inheritdoc */
    public $depends = [
        GraphiqlAsset::class,
    ];
}
