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
use Illuminate\Contracts\Support\Arrayable;

use function CraftCms\Cms\t;

class UserPreferencesViewModel implements Arrayable
{
    public bool $readOnly = false;

    /** @var array<string, mixed> */
    public array $preferences;

    /** @var array<int, array{label: string, value: string, data?: array<string, mixed>}> */
    public array $languageOptions;

    /** @var array<int, array{label: string, value: string, data?: array<string, mixed>}> */
    public array $localeOptions;

    /** @var array<int, array{label: string, value: string}> */
    public array $weekStartDayOptions;

    /** @var array<int, array{label: string, value: string, data?: array<string, mixed>|null}> */
    public array $timeZoneOptions;

    public string $orientation;

    public bool $isAdmin;

    public ?HtmlFragment $prefsHook = null;

    public function __construct(
        private readonly User $user,
    ) {
        $a11yDefaults = Cms::config()->accessibilityDefaults;

        $this->preferences = [
            'preferredLanguage' => $this->userLanguage(),
            'preferredLocale' => $this->userLocale(),
            'weekStartDay' => $user->getPreference('weekStartDay', Cms::config()->defaultWeekStartDay),
            'timeZone' => $user->getPreference('timeZone'),
            'useShapes' => $user->getPreference('useShapes') ?? $a11yDefaults['useShapes'] ?? false,
            'underlineLinks' => $user->getPreference('underlineLinks') ?? $a11yDefaults['underlineLinks'] ?? false,
            'disableAutofocus' => $user->getPreference('disableAutofocus') ?? $a11yDefaults['disableAutofocus'] ?? false,
            'notificationDuration' => $user->getPreference('notificationDuration') ?? $a11yDefaults['notificationDuration'] ?? 5000,
            'notificationPosition' => $user->getPreference('notificationPosition') ?? $a11yDefaults['notificationPosition'] ?? 'end-start',
            'slideoutPosition' => $user->getPreference('slideoutPosition') ?? $a11yDefaults['slideoutPosition'] ?? 'end',
            'showFieldHandles' => $user->getPreference('showFieldHandles') ?? false,
            'showExceptionView' => $user->getPreference('showExceptionView') ?? false,
            'profileTemplates' => $user->getPreference('profileTemplates') ?? false,
        ];

        $this->languageOptions = SelectOptions::getLanguageOptions(showLocalizedNames: true, appLocales: true);
        $this->localeOptions = [
            ['label' => t('Same as language'), 'value' => '__blank__'],
            ...SelectOptions::getLanguageOptions(showLocalizedNames: true),
        ];
        $this->weekStartDayOptions = $this->weekStartDayOptions();
        $this->timeZoneOptions = $this->timeZoneOptions();
        $this->orientation = I18N::getLocale()->getOrientation();
        $this->isAdmin = $user->isAdmin();

        $context = [
            ...app(TemplateGlobals::class)->resolve(),
            'user' => $user,
            'isNewUser' => false,
        ];

        $fragment = HtmlStack::capture(function () use ($context): string {
            return app(TemplateHooks::class)->invoke('cp.users.edit.prefs', $context);
        });

        $this->prefsHook = $fragment->isEmpty() ? null : $fragment;
    }

    public function toArray(): array
    {
        return [
            'readOnly' => $this->readOnly,
            'preferences' => $this->preferences,
            'languageOptions' => $this->languageOptions,
            'localeOptions' => $this->localeOptions,
            'weekStartDayOptions' => $this->weekStartDayOptions,
            'timeZoneOptions' => $this->timeZoneOptions,
            'orientation' => $this->orientation,
            'isAdmin' => $this->isAdmin,
            'prefsHook' => $this->prefsHook,
        ];
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

    private function timeZoneOptions(): array
    {
        return [
            [
                'label' => t('System time zone ({abbr})', ['abbr' => $this->systemTimeZoneAbbr()]),
                'value' => '__blank__',
            ],
            ...SelectOptions::getTimeZoneOptions(),
        ];
    }
}
