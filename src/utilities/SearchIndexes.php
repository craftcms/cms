<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use yii\base\Exception;

/**
 * SearchIndexes represents a SearchIndexes dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SearchIndexes extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Search Indexes');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'search-indexes';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        $iconPath = Craft::getAlias('@app/icons/search.svg');

        if ($iconPath === false) {
            throw new Exception('There was a problem getting the icon path.');
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerJsResource('js/SearchIndexesUtility.js');
        $view->registerJs('new Craft.SearchIndexesUtility(\'search-indexes\');');

        return $view->renderTemplate('_components/utilities/SearchIndexes');
    }
}
