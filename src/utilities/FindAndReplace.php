<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\findreplace\FindReplaceAsset;

/**
 * FindAndReplace represents a FindAndReplace dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FindAndReplace extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Find and Replace');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'find-replace';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/wand.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(FindReplaceAsset::class);
        $view->registerJs('new Craft.FindAndReplaceUtility(\'find-replace\');');

        return $view->renderTemplate('_components/utilities/FindAndReplace');
    }
}
