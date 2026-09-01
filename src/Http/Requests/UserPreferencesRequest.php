<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Validation\Rules\TimezoneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        return [
            'preferredLanguage' => ['sometimes', 'string', Rule::in(I18N::getAppLocaleIds()->all())],
            'preferredLocale' => ['sometimes', 'nullable', 'string', Rule::in(['__blank__', ...I18N::getAllLocaleIds()->all()])],
            'weekStartDay' => ['sometimes', 'integer', 'between:0,6'],
            'timeZone' => ['sometimes', 'nullable', 'string', Rule::when($this->input('timeZone') !== '__blank__', [new TimezoneRule])],
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

    /** @return array<string, string> */
    private function preferenceMap(): array
    {
        return [
            'preferredLanguage' => 'language',
            'preferredLocale' => 'locale',
            'weekStartDay' => 'weekStartDay',
            'timeZone' => 'timeZone',
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
