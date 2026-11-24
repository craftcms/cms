<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Http\Controllers\Users\PreferencesController;
use CraftCms\Cms\Http\Requests\UserPreferencesRequest;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Tab;
use CraftCms\Cms\Ui\Nodes\TemplateContent;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateGlobals;
use CraftCms\Cms\View\TemplateHooks;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class UserPreferencesViewModel extends ViewModel
{
    public function __construct(
        private readonly User $user,
        public readonly ?string $metadataHtml = null,
    ) {}

    public function ui(): UiPayload
    {
        $ui = Ui::make([
            Tab::make('general', t('General'), $this->generalNodes()),
            Tab::make('accessibility', t('Accessibility'), [
                Field::make(t('Display Settings'), Choice::make('displaySettings')
                    ->multiple()
                    ->options([
                        ['label' => t('Use shapes to represent statuses'), 'value' => 'useShapes'],
                        ['label' => t('Underline links'), 'value' => 'underlineLinks'],
                    ])),
                Field::make(t('General Settings'), Choice::make('generalSettings')
                    ->multiple()
                    ->options([
                        ['label' => t('Disable autofocus'), 'value' => 'disableAutofocus'],
                    ])),
                Field::make(t('Notification Duration'), Choice::make('notificationDuration')
                    ->withoutPlaceholder()
                    ->options([
                        ['label' => t('{num, number} seconds', ['num' => 2]), 'value' => '2000'],
                        ['label' => t('{num, number} seconds', ['num' => 5]), 'value' => '5000'],
                        ['label' => t('{num, number} seconds', ['num' => 10]), 'value' => '10000'],
                        ['label' => t('Show them indefinitely'), 'value' => '0'],
                    ]))
                    ->instructions(t('How long notifications should be shown before they disappear automatically.')),
                Field::make(t('Notification Position'), Choice::make('notificationPosition')
                    ->presentation(ChoicePresentation::Buttons)
                    ->options($this->notificationPositionOptions())),
                Field::make(t('Slideout Position'), Choice::make('slideoutPosition')
                    ->presentation(ChoicePresentation::Buttons)
                    ->options($this->slideoutPositionOptions())),
            ]),
        ])->when($this->user->isAdmin(), fn (Ui $ui) => $ui->add(
            Tab::make('development', t('Development'), [
                Field::make(t('Development Settings'), Choice::make('developmentSettings')
                    ->multiple()
                    ->options([
                        ['label' => t('Show field handles in edit forms'), 'value' => 'showFieldHandles'],
                        ['label' => t('Profile Twig templates when Dev Mode is disabled'), 'value' => 'profileTemplates'],
                        ['label' => t('Show full exception views when Dev Mode is disabled'), 'value' => 'showExceptionView'],
                    ])),
            ]),
        ));

        return app(UiResolver::class)->resolve($ui, new UiContext(values: $this->values()));
    }

    /** @return array{method: string, url: string} */
    public function submit(): array
    {
        return [
            'method' => 'patch',
            'url' => action([PreferencesController::class, 'update']),
        ];
    }

    /** @return list<Node> */
    private function generalNodes(): array
    {
        $nodes = [
            Field::make(t('Language'), Combobox::make('preferredLanguage')
                ->options(SelectOptions::getLanguageOptions(showLocalizedNames: true, appLocales: true))
                ->requireOptionMatch()
                ->showAllOnEmpty())
                ->instructions(t('The language that the control panel should use.')),
            Field::make(t('Formatting Locale'), Combobox::make('preferredLocale')
                ->options([
                    ['label' => t('Same as language'), 'value' => '__blank__'],
                    ...SelectOptions::getLanguageOptions(showLocalizedNames: true),
                ])
                ->requireOptionMatch()
                ->showAllOnEmpty())
                ->instructions(t('The locale that should be used for date and number formatting.')),
            Field::make(t('Week Start Day'), Choice::make('weekStartDay')
                ->withoutPlaceholder()
                ->options($this->weekStartDayOptions())),
            Field::make(t('Time Zone'), Combobox::make('timeZone')
                ->options([
                    [
                        'label' => t('System time zone ({abbr})', ['abbr' => $this->systemTimeZoneAbbr()]),
                        'value' => '__blank__',
                    ],
                    ...SelectOptions::getTimeZoneOptions(),
                ])
                ->requireOptionMatch()
                ->showAllOnEmpty()),
            Field::make(t('Theme'), Choice::make('theme')
                ->presentation(ChoicePresentation::Buttons)
                ->iconsOnly(false)
                ->options([
                    ['icon' => 'sun', 'label' => t('Light'), 'value' => Theme::Light->value],
                    ['icon' => 'moon', 'label' => t('Dark'), 'value' => Theme::Dark->value],
                    ['icon' => 'desktop', 'label' => t('System'), 'value' => Theme::System->value],
                ])),
        ];

        $hookHtml = $this->prefsHookHtml();

        if ($hookHtml !== '') {
            // Plugin markup; any assets it registers ship with the page.
            $nodes[] = TemplateContent::make('prefs-hook', $hookHtml)->trusted();
        }

        return $nodes;
    }

    /** @return array<string, mixed> */
    private function values(): array
    {
        $a11yDefaults = Cms::config()->accessibilityDefaults;
        $pref = fn (string $key, mixed $default = null): mixed => $this->user->getPreference($key) ?? $a11yDefaults[$key] ?? $default;
        $checked = fn (array $keys): array => array_values(array_filter($keys, fn (string $key): bool => (bool) $pref($key, false)));

        return [
            'preferredLanguage' => $this->userLanguage(),
            'preferredLocale' => $this->userLocale(),
            'weekStartDay' => (string) $this->user->getPreference('weekStartDay', Cms::config()->defaultWeekStartDay),
            'timeZone' => $this->user->getPreference('timeZone') ?? '__blank__',
            'theme' => Theme::forUser($this->user)->value,
            'displaySettings' => $checked(['useShapes', 'underlineLinks']),
            'generalSettings' => $checked(['disableAutofocus']),
            'notificationDuration' => (string) $pref('notificationDuration', 5000),
            'notificationPosition' => $pref('notificationPosition', 'end-start'),
            'slideoutPosition' => $pref('slideoutPosition', 'end'),
            ...($this->user->isAdmin() ? [
                'developmentSettings' => $checked(UserPreferencesRequest::adminOnlyPreferences()),
            ] : []),
        ];
    }

    private function prefsHookHtml(): string
    {
        $context = [
            ...app(TemplateGlobals::class)->resolve(),
            'user' => $this->user,
            'isNewUser' => false,
        ];

        return trim(app(TemplateHooks::class)->invoke('cp.users.edit.prefs', $context));
    }

    private function userLanguage(): string
    {
        $userLanguage = $this->user->getPreferredLanguage();

        if (
            ! $userLanguage ||
            I18N::getAppLocales()->doesntContain(fn (Locale $locale) => $locale->id === Env::parse($userLanguage))
        ) {
            return app()->getLocale();
        }

        return $userLanguage;
    }

    private function userLocale(): string
    {
        $userLocale = $this->user->getPreferredLocale();

        if (
            ! $userLocale ||
            I18N::getAllLocales()->doesntContain(fn (Locale $locale) => $locale->id === Env::parse($userLocale))
        ) {
            return Cms::config()->defaultCpLocale ?? app()->getLocale();
        }

        return $userLocale;
    }

    private function systemTimeZoneAbbr(): string
    {
        $timeZone = Cms::config()->timezone ?? ProjectConfig::get('system.timeZone');

        return $timeZone ? DateTimeHelper::timeZoneAbbreviation(Env::parse($timeZone)) : 'UTC';
    }

    /** @return list<array{label: string, value: string}> */
    private function weekStartDayOptions(): array
    {
        return collect(I18N::getLocale()->getWeekDayNames())
            ->map(fn (string $label, int $value): array => [
                'label' => $label,
                'value' => (string) $value,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{icon: string, label: string, value: string}> */
    private function notificationPositionOptions(): array
    {
        $ltr = I18N::getLocale()->getOrientation() === 'ltr';

        return [
            [
                'icon' => $ltr ? 'custom-icons/notification-top-left' : 'custom-icons/notification-top-right',
                'label' => $ltr ? t('Top-Left') : t('Top-Right'),
                'value' => 'start-start',
            ],
            [
                'icon' => $ltr ? 'custom-icons/notification-top-right' : 'custom-icons/notification-top-left',
                'label' => $ltr ? t('Top-Right') : t('Top-Left'),
                'value' => 'start-end',
            ],
            [
                'icon' => $ltr ? 'custom-icons/notification-bottom-left' : 'custom-icons/notification-bottom-right',
                'label' => $ltr ? t('Bottom-Left') : t('Bottom-Right'),
                'value' => 'end-start',
            ],
            [
                'icon' => $ltr ? 'custom-icons/notification-bottom-right' : 'custom-icons/notification-bottom-left',
                'label' => $ltr ? t('Bottom-Right') : t('Bottom-Left'),
                'value' => 'end-end',
            ],
        ];
    }

    /** @return list<array{icon: string, label: string, value: string}> */
    private function slideoutPositionOptions(): array
    {
        $ltr = I18N::getLocale()->getOrientation() === 'ltr';

        return [
            [
                'icon' => $ltr ? 'custom-icons/slideout-left' : 'custom-icons/slideout-right',
                'label' => $ltr ? t('Left') : t('Right'),
                'value' => 'start',
            ],
            [
                'icon' => $ltr ? 'custom-icons/slideout-right' : 'custom-icons/slideout-left',
                'label' => $ltr ? t('Right') : t('Left'),
                'value' => 'end',
            ],
        ];
    }
}
