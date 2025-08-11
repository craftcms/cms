<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\systemmessages\SystemMessagesAsset;
use CraftCms\Cms\CmsEdition;
use CraftCms\Cms\Utility\Utility;

/**
 * SystemMessages represents a System Messages utility.
 *

 * @since 6.0.0
 */
final readonly class SystemMessages extends Utility
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
    public static function icon(): string
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
