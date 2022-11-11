<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\queuemanager\QueueManagerAsset;

/**
 * Queue manager is a utility used for managing jobs in the Queue.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.4.0
 */
class QueueManager extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Queue Manager');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'queue-manager';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@appicons/play.svg');
    }

    /**
     * @inheritdoc
     */
    public static function toolbarHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/QueueManager/toolbar.twig');
    }

    /**
     * @inheritdoc
     */
    public static function footerHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/QueueManager/footer.twig');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(QueueManagerAsset::class);
        return $view->renderTemplate('_components/utilities/QueueManager/content.twig');
    }
}
