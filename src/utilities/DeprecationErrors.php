<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\deprecationerrors\DeprecationErrorsAsset;

/**
 * DeprecationErrors represents a DeprecationErrors dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeprecationErrors extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Deprecation Warnings');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'deprecation-errors';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@appicons/bug.svg');
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return Craft::$app->getDeprecator()->getTotalLogs();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(DeprecationErrorsAsset::class);

        return $view->renderTemplate('_components/utilities/DeprecationErrors', [
            'logs' => Craft::$app->getDeprecator()->getLogs()
        ]);
    }
}
