<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @since 6.0.0
 */
class UserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->craftUser();

        if (! $user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return ! $this->hasAny(self::adminOnlyPreferences());
    }

    /**
     * The preferences form submits some booleans as lists of the ones that are
     * checked; each list sets every boolean it covers.
     */
    protected function prepareForValidation(): void
    {
        $booleans = [];

        foreach (self::checkboxGroups() as $group => $keys) {
            if (! $this->exists($group)) {
                continue;
            }

            $checked = (array) $this->input($group);

            foreach ($keys as $key) {
                $booleans[$key] = in_array($key, $checked, true);
            }
        }

        $this->merge($booleans);
    }

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'preferredLanguage' => ['sometimes', 'string', Rule::in(I18N::getAppLocaleIds()->all())],
            'preferredLocale' => ['sometimes', 'nullable', 'string', Rule::in(['__blank__', ...I18N::getAllLocaleIds()->all()])],
            'weekStartDay' => ['sometimes', 'integer', 'between:0,6'],
            'timeZone' => ['sometimes', 'nullable', 'string', Rule::when($this->input('timeZone') !== '__blank__', [new TimezoneRule])],
            'theme' => ['sometimes', 'string', Rule::enum(Theme::class)],
            'useShapes' => ['sometimes', 'boolean'],
            'underlineLinks' => ['sometimes', 'boolean'],
            'disableAutofocus' => ['sometimes', 'boolean'],
            'notificationDuration' => ['sometimes', 'integer', Rule::in([0, 2000, 5000, 10000])],
            'notificationPosition' => ['sometimes', 'string', Rule::in(['start-start', 'start-end', 'end-start', 'end-end'])],
            'slideoutPosition' => ['sometimes', 'string', Rule::in(['start', 'end'])],
            'showFieldHandles' => ['sometimes', 'boolean'],
            'showExceptionView' => ['sometimes', 'boolean'],
            'profileTemplates' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, bool|int|string|null> */
    public function preferences(): array
    {
        $validated = $this->safe();
        $preferences = [];

        foreach ($this->preferenceMap() as $inputKey => $preferenceKey) {
            if (! $validated->has($inputKey)) {
                continue;
            }

            $value = $validated->input($inputKey);
            $preferences[$preferenceKey] = $value === '__blank__' ? null : $value;
        }

        return $preferences;
    }

    /** @return string[] */
    public static function adminOnlyPreferences(): array
    {
        return [
            'showFieldHandles',
            'showExceptionView',
            'profileTemplates',
        ];
    }

    /** @return array<string, list<string>> */
    private static function checkboxGroups(): array
    {
        return [
            'displaySettings' => ['useShapes', 'underlineLinks'],
            'generalSettings' => ['disableAutofocus'],
            'developmentSettings' => self::adminOnlyPreferences(),
        ];
    }

    /** @return array<string, string> */
    private function preferenceMap(): array
    {
        return [
            'preferredLanguage' => 'language',
            'preferredLocale' => 'locale',
            'weekStartDay' => 'weekStartDay',
            'timeZone' => 'timeZone',
            'theme' => 'theme',
            'useShapes' => 'useShapes',
            'underlineLinks' => 'underlineLinks',
            'disableAutofocus' => 'disableAutofocus',
            'notificationDuration' => 'notificationDuration',
            'notificationPosition' => 'notificationPosition',
            'slideoutPosition' => 'slideoutPosition',
            'showFieldHandles' => 'showFieldHandles',
            'showExceptionView' => 'showExceptionView',
            'profileTemplates' => 'profileTemplates',
        ];
    }
}
