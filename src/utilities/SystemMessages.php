<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\systemmessages\SystemMessagesAsset;

/**
 * SystemMessages represents a System Messages utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class SystemMessages extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'System Messages');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'system-messages';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@appicons/envelope.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        Craft::$app->requireEdition(Craft::Pro);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(SystemMessagesAsset::class);

        $messages = Craft::$app->getSystemMessages()->getAllMessages();

        return $view->renderTemplate('_components/utilities/SystemMessages', [
            'messages' => $messages,
        ]);
    }
}
