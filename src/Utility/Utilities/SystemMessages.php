<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\SystemMessage\SystemMessages as SystemMessagesService;
use CraftCms\Cms\Translation\Locale;
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

        $messages = app(SystemMessagesService::class)->getAllMessages();

        $locales = I18N::getSiteLocales()->map(fn (Locale $locale) => [
            'value' => $locale->id,
            'label' => $locale->getDisplayName(app()->getLocale()),
        ])->values()->all();

        return Html::tag('SystemMessages', attributes: [
            ':messages' => $messages->map(fn ($m) => [
                'key' => $m->key,
                'heading' => $m->heading,
                'subject' => $m->subject,
                'body' => $m->body,
            ])->values()->all(),
            ':locales' => $locales,
            ':is-multi-site' => Sites::isMultiSite(),
            'primary-language' => Sites::getPrimarySite()->getLanguage(),
        ]);
    }
}
