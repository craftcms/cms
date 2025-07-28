<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\enums\CmsEdition;
use craft\web\assets\systemmessages\SystemMessagesAsset;
use CraftCms\Cms\Utility\Utility;

/**
 * SystemMessages represents a System Messages utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 6.0.0
 */
class SystemMessages extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'System Messages');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'system-messages';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): ?string
    {
        return 'envelope';
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        Craft::$app->requireEdition(CmsEdition::Pro);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(SystemMessagesAsset::class);

        $messages = Craft::$app->getSystemMessages()->getAllMessages();

        return $view->renderTemplate('_components/utilities/SystemMessages/index.twig', [
            'messages' => $messages,
        ]);
    }
}
