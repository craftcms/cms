<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\systemmessages\SystemMessagesAsset;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Utility\Utility;

/**
 * SystemMessages represents a System Messages utility.
 *

 * @since 6.0.0
 */
final class SystemMessages extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return Craft::t('app', 'System Messages');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'system-messages';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'envelope';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        Craft::$app->requireEdition(Edition::Pro);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(SystemMessagesAsset::class);

        $messages = Craft::$app->getSystemMessages()->getAllMessages();

        return $view->renderTemplate('_components/utilities/SystemMessages/index.twig', [
            'messages' => $messages,
        ]);
    }
}
