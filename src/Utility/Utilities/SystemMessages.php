<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\systemmessages\SystemMessagesAsset;
use CraftCms\Cms\Edition;
use CraftCms\Cms\SystemMessage\SystemMessages as SystemMessagesService;
use CraftCms\Cms\Utility\Utility;

use function CraftCms\Cms\t;

/**
 * SystemMessages represents a System Messages utility.
 */
final class SystemMessages extends Utility
{
    #[\Override]
    public static function displayName(): string
    {
        return t('System Messages');
    }

    #[\Override]
    public static function id(): string
    {
        return 'system-messages';
    }

    #[\Override]
    public static function icon(): string
    {
        return 'envelope';
    }

    #[\Override]
    public static function contentHtml(): string
    {
        Edition::require(Edition::Pro);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(SystemMessagesAsset::class);

        return $view->renderTemplate('_components/utilities/SystemMessages/index.twig', [
            'messages' => app(SystemMessagesService::class)->getAllMessages(),
        ]);
    }
}
