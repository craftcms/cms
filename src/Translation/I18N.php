<?php

namespace CraftCms\Cms\Translation;

use Craft;
use craft\models\Site;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Json;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use ResourceBundle;
use Stringable;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Translator;

#[Singleton]
final readonly class I18N
{
    /**
     * @var Collection<string> All of the known locales
     *
     * @see getAllLocales()
     */
    private Collection $allLocaleIds;

    /**
     * @var Collection<string, bool>
     *
     * @see getAppLocaleIds()
     */
    private Collection $appLocaleIds;

    /**
     * @var Collection<Locale>
     *
     * @see getAppLocales()
     */
    private Collection $appLocales;

    public function __construct(
        private GeneralConfig $generalConfig,
        private Translator $translator,
    ) {}

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
            ->map(function (string $locale) {
                return str_replace('_', '-', $locale);
            })
            ->when(
                ! empty($this->generalConfig->localeAliases),
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
        return $this->getAllLocaleIds()->map(function (string $localeId) {
            return new Locale($localeId, $this->generalConfig->localeAliases[$localeId] ?? []);
        });
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
        return $this->appLocales ??= $this->getAppLocaleIds()->map(function (string $localeId) {
            return new Locale(...array_merge([
                'id' => $localeId,
            ], $this->generalConfig->localeAliases[$localeId] ?? []));
        });
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
     * Returns a collection of the site locale IDs.
     *
     * @return Collection<string> A collection of locale IDs.
     */
    public function getSiteLocaleIds(): Collection
    {
        return collect(Craft::$app->getSites()->getAllSites())
            ->map(function (Site $site) {
                return $site->language;
            })
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
        return $this->getSiteLocaleIds()->map(function (string $localeId) {
            return new Locale(...array_merge([
                'id' => $localeId,
            ], $this->generalConfig->localeAliases[$localeId] ?? []));
        });
    }

    /**
     * Returns a list of locales that are editable by the current user.
     *
     * @return Collection<Locale>
     */
    public function getEditableLocales(): Collection
    {
        if (! Craft::$app->getIsMultiSite()) {
            return $this->getSiteLocales();
        }

        return $this->getSiteLocales()->filter(function (Locale $locale) {
            return Craft::$app->getUser()->checkPermission('editLocale:'.$locale->id);
        });
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

        $translation = $this->translator->translate($message, $parameters, $category, $locale);

        if ($this->generalConfig->translationDebugOutput) {
            $char = match ($category) {
                'site' => '$',
                'app' => '@',
                default => '%',
            };

            $translation = $char.$translation.$char;
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
