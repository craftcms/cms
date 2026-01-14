<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use ResourceBundle;
use Stringable;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Translator;

#[Singleton]
final class I18N
{
    /**
     * @var Collection<string> All of the known locales
     *
     * @see getAllLocales()
     */
    private ?Collection $allLocaleIds = null;

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

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Translator $translator,
    ) {}

    public function getFormatter(): Formatter
    {
        return $this->getFormattingLocale()->getFormatter();
    }

    public function getLocale(): Locale
    {
        return $this->getLocaleById(app()->getLocale());
    }

    public function getFormattingLocale(): Locale
    {
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

        if ($this->generalConfig->defaultCpLocale) {
            return $this->getLocaleById($this->generalConfig->defaultCpLocale);
        }

        return $this->getLocale();
    }

    /**
     * Returns a locale by its ID.
     */
    public function getLocaleById(string $localeId): Locale
    {
        return new Locale(...array_merge([
            'id' => $localeId,
        ], $this->generalConfig->localeAliases[$localeId] ?? []));
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
        return $this->allLocaleIds ??= collect(ResourceBundle::getLocales(''))
            ->map(fn (string $locale) => str_replace('_', '-', $locale))
            ->unless(
                empty($this->generalConfig->localeAliases),
                fn (Collection $localeIds) => $localeIds
                    ->merge(array_keys($this->generalConfig->localeAliases))
                    ->unique()
                    ->sort(),
            );
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
        ], $this->generalConfig->localeAliases[$localeId] ?? [])));
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
        return $this->appLocales ??= $this->getAppLocaleIds()->map(fn (string $localeId) => new Locale(...array_merge([
            'id' => $localeId,
        ], $this->generalConfig->localeAliases[$localeId] ?? [])));
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
        return $this->getSiteLocaleIds()->map(fn (string $localeId) => new Locale(...array_merge([
            'id' => $localeId,
        ], $this->generalConfig->localeAliases[$localeId] ?? [])));
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

    public function translate(string|Stringable $message, array $parameters = [], ?string $category = null, ?string $locale = null): string
    {
        if (str_starts_with($message, 't9n:')) {
            $args = Json::decode(substr($message, 4));

            return $this->translate(...$args);
        }

        $locale ??= str_replace('_', '-', app()->getLocale());

        $translation = $this->translator->translate($message, $parameters, $category, $locale);

        if ($this->generalConfig->translationDebugOutput) {
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
            return __($message, $parameters, $locale);
        }

        return $translation;
    }

    public function addCategorySources(CategorySource ...$categories): void
    {
        $this->translator->addCategorySources(...$categories);
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
        foreach ($this->generalConfig->extraAppLocales as $localeId) {
            $this->appLocaleIds->put($localeId, true);
        }

        if ($this->generalConfig->defaultCpLanguage) {
            $this->appLocaleIds->put($this->generalConfig->defaultCpLanguage, true);
        }
    }
}
