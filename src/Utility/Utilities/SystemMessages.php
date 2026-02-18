<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\systemmessages\SystemMessagesAsset;
use CraftCms\Cms\Edition;
use CraftCms\Cms\SystemMessage\SystemMessages as SystemMessagesService;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * SystemMessages represents a System Messages utility.
 */
final class SystemMessages extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('System Messages');
    }

    #[Override]
    public static function id(): string
    {
        return 'system-messages';
    }

    #[Override]
    public static function icon(): string
    {
        return 'envelope';
    }

    #[Override]
    public static function contentHtml(): string
    {
        Edition::require(Edition::Pro);

        Craft::$app->getView()->registerAssetBundle(SystemMessagesAsset::class);

        return template('_components/utilities/SystemMessages/index', [
            'messages' => app(SystemMessagesService::class)->getAllMessages(),
        ]);
    }
}
