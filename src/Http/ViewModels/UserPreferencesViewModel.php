<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Cms\View\TemplateGlobals;
use CraftCms\Cms\View\TemplateHooks;

use function CraftCms\Cms\t;

class UserPreferencesViewModel extends ViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array<string, mixed> */
    public function preferences(): array
    {
        $a11yDefaults = Cms::config()->accessibilityDefaults;

        return [
            'preferredLanguage' => $this->userLanguage(),
            'preferredLocale' => $this->userLocale(),
            'weekStartDay' => $this->user->getPreference('weekStartDay', Cms::config()->defaultWeekStartDay),
            'timeZone' => $this->user->getPreference('timeZone'),
            'useShapes' => $this->user->getPreference('useShapes') ?? $a11yDefaults['useShapes'] ?? false,
            'underlineLinks' => $this->user->getPreference('underlineLinks') ?? $a11yDefaults['underlineLinks'] ?? false,
            'disableAutofocus' => $this->user->getPreference('disableAutofocus') ?? $a11yDefaults['disableAutofocus'] ?? false,
            'notificationDuration' => $this->user->getPreference('notificationDuration') ?? $a11yDefaults['notificationDuration'] ?? 5000,
            'notificationPosition' => $this->user->getPreference('notificationPosition') ?? $a11yDefaults['notificationPosition'] ?? 'end-start',
            'slideoutPosition' => $this->user->getPreference('slideoutPosition') ?? $a11yDefaults['slideoutPosition'] ?? 'end',
            'showFieldHandles' => $this->user->getPreference('showFieldHandles') ?? false,
            'showExceptionView' => $this->user->getPreference('showExceptionView') ?? false,
            'profileTemplates' => $this->user->getPreference('profileTemplates') ?? false,
        ];
    }

    /** @return array<int, array{label: string, value: string, data?: array<string, mixed>}> */
    public function languageOptions(): array
    {
        return SelectOptions::getLanguageOptions(showLocalizedNames: true, appLocales: true);
    }

    /** @return array<int, array{label: string, value: string, data?: array<string, mixed>}> */
    public function localeOptions(): array
    {
        return [
            ['label' => t('Same as language'), 'value' => '__blank__'],
            ...SelectOptions::getLanguageOptions(showLocalizedNames: true),
        ];
    }

    public function isAdmin(): bool
    {
        return $this->user->isAdmin();
    }

    public function prefsHook(): ?HtmlFragment
    {
        $context = [
            ...app(TemplateGlobals::class)->resolve(),
            'user' => $this->user,
            'isNewUser' => false,
        ];

        $fragment = HtmlStack::capture(fn (): string => app(TemplateHooks::class)->invoke('cp.users.edit.prefs', $context));

        return $fragment->isEmpty() ? null : $fragment;
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

    /** @return array<int, array{label: string, value: string}> */
    public function weekStartDayOptions(): array
    {
        return collect(I18N::getLocale()->getWeekDayNames())
            ->map(fn (string $label, int $value): array => [
                'label' => $label,
                'value' => (string) $value,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{label: string, value: string, data?: array<string, mixed>|null}> */
    public function timeZoneOptions(): array
    {
        return [
            [
                'label' => t('System time zone ({abbr})', ['abbr' => $this->systemTimeZoneAbbr()]),
                'value' => '__blank__',
            ],
            ...SelectOptions::getTimeZoneOptions(),
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    public function notificationDurationOptions(): array
    {
        return [
            ['label' => t('{num, number} seconds', ['num' => 2]), 'value' => '2000'],
            ['label' => t('{num, number} seconds', ['num' => 5]), 'value' => '5000'],
            ['label' => t('{num, number} seconds', ['num' => 10]), 'value' => '10000'],
            ['label' => t('Show them indefinitely'), 'value' => '0'],
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    public function displaySettingOptions(): array
    {
        return [
            ['label' => t('Use shapes to represent statuses'), 'value' => 'useShapes'],
            ['label' => t('Underline links'), 'value' => 'underlineLinks'],
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    public function generalSettingOptions(): array
    {
        return [
            ['label' => t('Disable autofocus'), 'value' => 'disableAutofocus'],
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    public function developmentSettingOptions(): array
    {
        return [
            ['label' => t('Show field handles in edit forms'), 'value' => 'showFieldHandles'],
            ['label' => t('Profile Twig templates when Dev Mode is disabled'), 'value' => 'profileTemplates'],
            ['label' => t('Show full exception views when Dev Mode is disabled'), 'value' => 'showExceptionView'],
        ];
    }

    /** @return array<int, array{icon: string, label: string, value: string}> */
    public function notificationPositionOptions(): array
    {
        $orientation = I18N::getLocale()->getOrientation();

        return [
            [
                'icon' => $orientation === 'ltr' ? 'custom-icons/notification-top-left' : 'custom-icons/notification-top-right',
                'label' => $orientation === 'ltr' ? t('Top-Left') : t('Top-Right'),
                'value' => 'start-start',
            ],
            [
                'icon' => $orientation === 'ltr' ? 'custom-icons/notification-top-right' : 'custom-icons/notification-top-left',
                'label' => $orientation === 'ltr' ? t('Top-Right') : t('Top-Left'),
                'value' => 'start-end',
            ],
            [
                'icon' => $orientation === 'ltr' ? 'custom-icons/notification-bottom-left' : 'custom-icons/notification-bottom-right',
                'label' => $orientation === 'ltr' ? t('Bottom-Left') : t('Bottom-Right'),
                'value' => 'end-start',
            ],
            [
                'icon' => $orientation === 'ltr' ? 'custom-icons/notification-bottom-right' : 'custom-icons/notification-bottom-left',
                'label' => $orientation === 'ltr' ? t('Bottom-Right') : t('Bottom-Left'),
                'value' => 'end-end',
            ],
        ];
    }

    /** @return array<int, array{icon: string, label: string, value: string}> */
    public function slideoutPositionOptions(): array
    {
        $orientation = I18N::getLocale()->getOrientation();

        return [
            [
                'icon' => $orientation === 'ltr' ? 'custom-icons/slideout-left' : 'custom-icons/slideout-right',
                'label' => $orientation === 'ltr' ? t('Left') : t('Right'),
                'value' => 'start',
            ],
            [
                'icon' => $orientation === 'ltr' ? 'custom-icons/slideout-right' : 'custom-icons/slideout-left',
                'label' => $orientation === 'ltr' ? t('Right') : t('Left'),
                'value' => 'end',
            ],
        ];
    }
}
