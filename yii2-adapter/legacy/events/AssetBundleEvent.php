<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use yii\web\AssetBundle;

/**
 * Asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class AssetBundleEvent extends Event
{
    /**
     * @var string The name of the asset bundle
     */
    public string $bundleName;

    /**
     * @var int|null The position of the asset bundle
     */
    public ?int $position = null;

    /**
     * @var AssetBundle The asset bundle instance
     */
    public AssetBundle $bundle;
}
