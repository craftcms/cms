<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\getLocale;
use function CraftCms\Cms\t;

readonly class PreferencesController
{
    use EditUserTrait;
    use RespondsWithFlash;

    public function index(Request $request, I18N $i18N, GeneralConfig $generalConfig, ProjectConfig $projectConfig): CpScreenResponse
    {
        $currentUser = $request->craftUser();
        if (! $currentUser) {
            abort(401);
        }

        $user = $currentUser->asElement();

        $response = $this->asEditUserScreen($user, self::SCREEN_PREFERENCES);

        // user language
        $userLanguage = $user->getPreferredLanguage();

        if (
            ! $userLanguage ||
            $i18N->getAppLocales()->doesntContain(fn (Locale $locale) => $locale->id === Env::parse($userLanguage))
        ) {
            $userLanguage = getLocale();
        }

        // user locale
        $userLocale = $user->getPreferredLocale();

        if (
            ! $userLocale ||
            $i18N->getAllLocales()->doesntContain(fn (Locale $locale) => $locale->id === Env::parse($userLocale))
        ) {
            $userLocale = $generalConfig->defaultCpLocale;
        }

        // time zone
        // (can't call `Craft::$app->getTimeZone()` here because that could be set to the user preference)
        $timeZone = $generalConfig->timezone ?? $projectConfig->get('system.timeZone');
        $timeZoneAbbr = $timeZone ? DateTimeHelper::timeZoneAbbreviation(Env::parse($timeZone)) : 'UTC';

        $response->action('users/save-preferences');
        $response->contentTemplate('users/_preferences', compact(
            'userLanguage',
            'userLocale',
            'timeZoneAbbr',
        ));

        return $response;
    }

    public function store(Request $request, Users $users): Response
    {
        $user = $request->craftUser();
        if (! $user) {
            abort(401);
        }

        $preferredLocale = $request->input('preferredLocale', $user->getPreference('locale')) ?: null;

        if ($preferredLocale === '__blank__') {
            $preferredLocale = null;
        }

        $timeZone = $request->input('timeZone', $user->getPreference('timezone')) ?: null;

        if ($timeZone === '__blank__') {
            $timeZone = null;
        }

        $preferences = [
            'language' => $request->input('preferredLanguage', $user->getPreference('language')),
            'locale' => $preferredLocale,
            'weekStartDay' => $request->input('weekStartDay', $user->getPreference('weekStartDay')),
            'timeZone' => $timeZone,
            'useShapes' => (bool) $request->input('useShapes', $user->getPreference('useShapes')),
            'underlineLinks' => (bool) $request->input('underlineLinks', $user->getPreference('underlineLinks')),
            'disableAutofocus' => $request->input('disableAutofocus', $user->getPreference('disableAutofocus')),
            'notificationDuration' => $request->input('notificationDuration', $user->getPreference('notificationDuration')),
            'notificationPosition' => $request->input('notificationPosition', $user->getPreference('notificationPosition')),
            'slideoutPosition' => $request->input('slideoutPosition', $user->getPreference('slideoutPosition')),
        ];

        if ($user->isAdmin()) {
            $preferences = array_merge($preferences, [
                'showFieldHandles' => (bool) $request->input('showFieldHandles', $user->getPreference('showFieldHandles')),
                'showExceptionView' => (bool) $request->input('showExceptionView', $user->getPreference('showExceptionView')),
                'profileTemplates' => (bool) $request->input('profileTemplates', $user->getPreference('profileTemplates')),
            ]);
        }

        $users->saveUserPreferences($user, $preferences);

        return $this->asSuccess(t('Preferences saved.'));
    }
}
