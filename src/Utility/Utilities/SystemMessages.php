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
use Override;

use function CraftCms\Cms\t;

/**
 * SystemMessages represents a System Messages utility.
 *
 * @since 6.0.0
 */
class SystemMessages extends Utility
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

        $systemMessages = app(SystemMessagesService::class);
        $messages = $systemMessages->getAllMessages();

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
                'textExpanderTriggers' => self::textExpanderTriggers($systemMessages->getTemplateVariables($m)),
            ])->values()->all(),
            ':locales' => $locales,
            'primary-language' => Sites::getPrimarySite()->getLanguage(),
        ]);
    }

    /**
     * @param  array<string, string>  $variables
     * @return list<array<string, mixed>>
     */
    private static function textExpanderTriggers(array $variables): array
    {
        $options = collect($variables)
            ->map(function (string $label, string $name): array {
                $value = sprintf('{{%s}}', $name);

                return [
                    'label' => $label,
                    'value' => $value,
                    'keywords' => [$name],
                    'data' => ['hint' => $value],
                ];
            })
            ->values()
            ->all();

        return [[
            'trigger' => '{{',
            'boundary' => 'anywhere',
            'label' => t('Message Variables'),
            'options' => $options,
        ]];
    }
}
