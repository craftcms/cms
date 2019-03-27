<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\base\Volume;
use craft\helpers\Html;
use craft\web\assets\assetindexes\AssetIndexesAsset;

/**
 * RebuildProjectConfig represents a RebuildProjectConfig utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RebuildProjectConfig extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Rebuild Project Config');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'rebuild-project-config';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/tools.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/RebuildProjectConfig');
    }
}
