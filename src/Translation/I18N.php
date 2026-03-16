<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use ResourceBundle;
use Stringable;
use Yiisoft\I18n\Locale as YiisoftLocale;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Translator;

#[Singleton]
class I18N
{
    private const string CONTEXT_LOCALE = 'craft.locale';

    private const string CONTEXT_FORMATTING_LOCALE = 'craft.formattingLocale';

    /**
     * @var Collection<string> All of the known locales
     *
     * @see getAllLocaleIds()
     */
    private ?Collection $allLocaleIds = null;

    /**
     * @var array[]
     *
     * @see getAllLocaleIds()
     */
    private array $localeAliases;

    /**
     * @var Collection<string, bool>
     *
     * @see getAppLocaleIds()
     */
    private ?Collection $appLocaleIds = null;

    /**
     * @var Collection<Locale>
     *
     * @see getAppLocales()
     */
    private ?Collection $appLocales = null;

    /**
     * @var array<string, list<CategorySource>>
     */
    private array $registeredCategories = [];

    public function __construct(
        private readonly Translator $translator,
    ) {}

    public function getFormatter(): Formatter
    {
        return $this->getFormattingLocale()->getFormatter();
    }

    public function getLocale(): Locale
    {
        if (Context::hasHidden(self::CONTEXT_LOCALE)) {
            return $this->getLocaleById(Context::getHidden(self::CONTEXT_LOCALE));
        }

        return $this->getLocaleById(app()->getLocale());
    }

    public function getFormattingLocale(): Locale
    {
        if (Context::hasHidden(self::CONTEXT_FORMATTING_LOCALE)) {
            return $this->getLocaleById(Context::getHidden(self::CONTEXT_FORMATTING_LOCALE));
        }

        if (app()->runningInConsole()) {
            return $this->getLocale();
        }

        if (! request()->isCpRequest()) {
            return $this->getLocale();
        }

        if (Cms::isInstalled() && $user = Auth::user()) {
            // If they have a preferred locale, use it
            if (($locale = Users::getUserPreference($user->id, 'locale')) !== null) {
                return $this->getLocaleById($locale);
            }

            if (
                ($language = Users::getUserPreference($user->id, 'language')) !== null &&
                $this->validateAppLocaleId($language)
            ) {
                return $this->getLocaleById($language);
            }
        }

        if (Cms::config()->defaultCpLocale) {
            return $this->getLocaleById(Cms::config()->defaultCpLocale);
        }

        return $this->getLocale();
    }

    public function withLocale(string $language, ?string $formattingLocaleId, callable $callback): mixed
    {
        $previousLanguage = app()->getLocale();
        $previousLocale = Context::hasHidden(self::CONTEXT_LOCALE)
            ? Context::getHidden(self::CONTEXT_LOCALE)
            : null;
        $previousFormattingLocale = Context::hasHidden(self::CONTEXT_FORMATTING_LOCALE)
            ? Context::getHidden(self::CONTEXT_FORMATTING_LOCALE)
            : null;

        Context::addHidden(self::CONTEXT_LOCALE, $language);
        Context::addHidden(self::CONTEXT_FORMATTING_LOCALE, $formattingLocaleId ?? $language);
        app()->setLocale($language);

        try {
            return $callback();
        } finally {
            app()->setLocale($previousLanguage);

            if ($previousLocale === null) {
                Context::forgetHidden(self::CONTEXT_LOCALE);
            } else {
                Context::addHidden(self::CONTEXT_LOCALE, $previousLocale);
            }

            if ($previousFormattingLocale === null) {
                Context::forgetHidden(self::CONTEXT_FORMATTING_LOCALE);
            } else {
                Context::addHidden(self::CONTEXT_FORMATTING_LOCALE, $previousFormattingLocale);
            }
        }
    }

    /**
     * Returns a locale by its ID.
     */
    public function getLocaleById(string $localeId): Locale
    {
        // make sure we've defined $this->_localeAliases
        $this->getAllLocaleIds();

        return new Locale(...array_merge([
            'id' => $localeId,
        ], $this->localeAliases[$localeId] ?? []));
    }

    /**
     * Returns a collection of all known locale IDs, according to the Intl extension.
     *
     * @return Collection<string>
     *
     * @link https://php.net/manual/en/resourcebundle.locales.php
     */
    public function getAllLocaleIds(): Collection
    {
        if (isset($this->allLocaleIds)) {
            return $this->allLocaleIds;
        }

        $allLocaleIds = ResourceBundle::getLocales('');
        $this->localeAliases = Cms::config()->localeAliases;

        // Hyphens, not underscores
        foreach ($allLocaleIds as $i => $locale) {
            $allLocaleIds[$i] = str_replace('_', '-', $locale);
        }

        $allLocaleIds = array_flip($allLocaleIds);

        // `no` wasn’t added until ICU 69
        if (! isset($allLocaleIds['no']) && isset($allLocaleIds['nb'])) {
            $this->localeAliases['no'] ??= [
                'aliasOf' => 'nb',
                'displayName' => 'Norwegian',
            ];
        }

        if (! empty($this->localeAliases)) {
            $allLocaleIds = array_merge($allLocaleIds, array_flip(array_keys($this->localeAliases)));
            ksort($allLocaleIds);
        }

        return $this->allLocaleIds = collect(array_keys($allLocaleIds));
    }

    /**
     * Returns a collection of all known locales.
     *
     * @return Collection<Locale> A collection of [[Locale]] objects.
     *
     * @see getAllLocaleIds()
     */
    public function getAllLocales(): Collection
    {
        return $this->getAllLocaleIds()->map(fn (string $localeId) => new Locale(...array_merge([
            'id' => $localeId,
        ], $this->localeAliases[$localeId] ?? [])));
    }

    /**
     * Returns a collection of the locale IDs which Craft has been translated into.
     * The list of locales is based on whatever files exist
     * in `vendor/craftcms/cms/resources/translations/`.
     *
     * @return Collection<string> A collection of locale IDs.
     */
    public function getAppLocaleIds(): Collection
    {
        $this->defineAppLocales();

        return $this->appLocaleIds->keys();
    }

    /**
     * Returns a collection of locales that Craft is translated into.
     * The list of locales is based on whatever files exist
     * in `vendor/craftcms/cms/resources/translations/`.
     *
     * @return Collection<Locale> An array of [[Locale]] objects.
     */
    public function getAppLocales(): Collection
    {
        // make sure we've defined $this->_localeAliases
        $this->getAllLocaleIds();

        return $this->appLocales ??= $this->getAppLocaleIds()->map(fn (string $localeId) => new Locale(...array_merge([
            'id' => $localeId,
        ], $this->localeAliases[$localeId] ?? [])));
    }

    /**
     * Returns whether the given locale ID is a supported app locale ID.
     */
    public function validateAppLocaleId(string $localeId): bool
    {
        $this->defineAppLocales();

        return $this->appLocaleIds->has($localeId);
    }

    /**
     * Normalizes a language into the correct format (e.g. `en-US`).
     */
    public function normalizeLanguage(string $language): string
    {
        $language = strtolower(str_replace('_', '-', $language));

        $allLanguages = $this->getAllLocaleIds()->all();
        $lcLanguages = array_map(strtolower(...), $allLanguages);
        $allLanguages = array_combine($lcLanguages, $allLanguages);

        if (! isset($allLanguages[$language])) {
            throw new InvalidArgumentException('Invalid language: '.$language);
        }

        return $allLanguages[$language];
    }

    /**
     * Normalizes a user-submitted number for use in code and/or to be saved into the database.
     *
     * Group symbols are removed (e.g. 1,000,000 => 1000000), and decimals are converted to a periods, if the current
     * locale uses something else.
     *
     * @param  mixed  $number  The number that should be normalized.
     * @param  string|null  $localeId  The locale ID that the number is set in
     */
    public function normalizeNumber(mixed $number, ?string $localeId = null): mixed
    {
        if (! is_string($number)) {
            return $number;
        }

        $locale = match (true) {
            $localeId === null => $this->getFormattingLocale(),
            $localeId === app()->getLocale() => $this->getLocale(),
            default => $this->getLocaleById($localeId),
        };

        $decimalSymbol = $locale->getNumberSymbol(Locale::SYMBOL_DECIMAL_SEPARATOR);
        $groupSymbol = $locale->getNumberSymbol(Locale::SYMBOL_GROUPING_SEPARATOR);

        // Remove any group symbols and use a period for the decimal symbol
        return str_replace([$groupSymbol, $decimalSymbol], ['', '.'], $number);
    }

    /**
     * Returns a collection of the site locale IDs.
     *
     * @return Collection<string> A collection of locale IDs.
     */
    public function getSiteLocaleIds(): Collection
    {
        return Sites::getAllSites()
            ->map(fn (Site $site) => $site->getLanguage())
            ->unique()
            ->values();
    }

    /**
     * Returns a collection of the site locales.
     *
     * @return Collection<Locale> A collection of [[Locale]] objects.
     */
    public function getSiteLocales(): Collection
    {
        // make sure we've defined $this->_localeAliases
        $this->getAllLocaleIds();

        return $this->getSiteLocaleIds()->map(fn (string $localeId) => new Locale(...array_merge([
            'id' => $localeId,
        ], $this->localeAliases[$localeId] ?? [])));
    }

    /**
     * Returns a list of locales that are editable by the current user.
     *
     * @return Collection<Locale>
     */
    public function getEditableLocales(): Collection
    {
        if (! Sites::isMultiSite()) {
            return $this->getSiteLocales();
        }

        return $this->getSiteLocales()->filter(fn (Locale $locale) => Gate::check('editLocale:'.$locale->id));
    }

    /**
     * Returns a collection of the editable locale IDs.
     *
     * @return Collection<string>
     */
    public function getEditableLocaleIds(): Collection
    {
        return $this->getEditableLocales()->map(fn (Locale $locale) => $locale->id);
    }

    public function translate(string|Stringable|null $message, array $parameters = [], ?string $category = null, ?string $locale = null): string
    {
        if (! $message) {
            return '';
        }

        if (str_starts_with($message, 't9n:')) {
            $args = Json::decode(substr($message, 4));

            return $this->translate(...$args);
        }

        $locale ??= str_replace('_', '-', app()->getLocale());

        $translation = $this->translator->translate($message, $parameters, $category, $locale);

        if (Cms::config()->translationDebugOutput) {
            $char = match ($category) {
                'site' => '$',
                'app' => '@',
                default => '%',
            };

            $translation = $char.$translation.$char;
        }

        /**
         * If we don't have a translation for the message.
         * Translate it using Laravel's translations.
         */
        if ($translation === (string) $message) {
            $result = __($message, $parameters, $locale);

            // We're dealing with a message that's equal to a translation file (for example 'site')
            if (is_array($result)) {
                return $translation;
            }

            return $result;
        }

        return $translation;
    }

    public function addCategorySources(CategorySource ...$categories): void
    {
        foreach ($categories as $category) {
            $this->registeredCategories[$category->getName()][] = $category;
        }

        $this->translator->addCategorySources(...$categories);
    }

    /**
     * Returns all translations for a given locale, organized by category.
     *
     * Only includes messages where the translation differs from the source key.
     * Handles locale fallback (e.g., `fr-CA` → `fr`).
     *
     * @return array<string, array<string, string>> Nested array of `[category => [sourceMessage => translation]]`
     */
    public function getAllTranslationsForLocale(string $locale): array
    {
        $result = [];

        // Normalize underscores to hyphens (BCP 47 format)
        $locale = str_replace('_', '-', $locale);

        // Build the locale fallback chain (e.g., fr-CA → fr)
        $localesToCheck = [$locale];
        $yiisoftLocale = new YiisoftLocale($locale);
        $fallback = $yiisoftLocale->fallbackLocale();

        if ($fallback->asString() !== $locale) {
            // Prepend the fallback so the exact locale wins when merged on top
            array_unshift($localesToCheck, $fallback->asString());
        }

        foreach ($this->registeredCategories as $categoryName => $sources) {
            $categoryTranslations = [];

            // Use the last source (matching Translator's LIFO resolution order)
            $source = end($sources);

            foreach ($localesToCheck as $localeToCheck) {
                $messages = $source->getMessages($localeToCheck);

                foreach ($messages as $key => $data) {
                    // Later locale (more specific) overwrites earlier (fallback)
                    $categoryTranslations[$key] = $data['message'];
                }
            }

            // Filter out untranslated messages (where translation === source key)
            $categoryTranslations = array_filter(
                $categoryTranslations,
                fn (string $translation, string $key) => $translation !== $key,
                ARRAY_FILTER_USE_BOTH,
            );

            if (! empty($categoryTranslations)) {
                $result[$categoryName] = $categoryTranslations;
            }
        }

        return $result;
    }

    /**
     * Prepares a source translation to be lazy-translated with [[translate()]].
     *
     * @param  string  $message  The message to be translated.
     * @param  array  $params  The parameters that will be used to replace the corresponding placeholders in the message.
     * @param  ?string  $category  The message category.
     * @param  ?string  $locale  The language code (e.g. `en-US`, `en`). If this is `null`, the current
     *                           language will be used by default.
     * @return string The translated message.
     */
    public function prep(string $message, array $params = [], ?string $category = null, ?string $locale = null): string
    {
        return 't9n:'.Json::encode(func_get_args());
    }

    private function defineAppLocales(): void
    {
        if (isset($this->appLocaleIds)) {
            return;
        }

        $this->appLocaleIds = collect([
            'en-US' => true,
            'ar' => true,
            'cs' => true,
            'da' => true,
            'de' => true,
            'de-CH' => true,
            'el' => true,
            'en' => true,
            'en-GB' => true,
            'es' => true,
            'fa' => true,
            'fr' => true,
            'fr-CA' => true,
            'he' => true,
            'hu' => true,
            'is' => true,
            'it' => true,
            'ja' => true,
            'ko' => true,
            'nb' => true,
            'nl' => true,
            'nn' => true,
            'pl' => true,
            'pt' => true,
            'ru' => true,
            'sk' => true,
            'sv' => true,
            'th' => true,
            'tr' => true,
            'uk' => true,
            'zh' => true,
        ]);

        // Add in any extra locales defined by the config
        foreach (Cms::config()->extraAppLocales as $localeId) {
            $this->appLocaleIds->put($localeId, true);
        }

        if (Cms::config()->defaultCpLanguage) {
            $this->appLocaleIds->put(Cms::config()->defaultCpLanguage, true);
        }
    }
}
